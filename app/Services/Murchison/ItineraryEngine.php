<?php

namespace App\Services\Murchison;

use App\Models\Activity;
use App\Models\TravelUpdate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * SMART ITINERARY ENGINE (primary module of the system).
 *
 * A transparent, rule-based planner tailored to Murchison Falls National Park:
 *  1. Score every active activity against the visitor's interests, fitness, budget tier,
 *     season (wet/dry) and "must-do" picks (e.g. added from a 360° hotspot).
 *  2. Greedily place the best activities into a day × time-slot grid
 *     (dawn, morning, afternoon, evening, night), respecting travel time on the first and
 *     last day, the activity's allowed slots, its length and a daily pace limit.
 *  3. Build a timed day-by-day schedule with travel legs, Paraa ferry crossings,
 *     overnight stays, tips and per-item cost.
 *  4. Cost the plan with the Travel Budgeting module (UWA tiered pricing).
 *  5. Explain every decision (reasons, skipped activities, warnings, packing list).
 */
class ItineraryEngine
{
    public const SLOT_ORDER = ['dawn', 'morning', 'afternoon', 'evening', 'night'];

    public function __construct(private BudgetCalculator $budget)
    {
    }

    public static function defaults(): array
    {
        return [
            'start_date' => now()->addDays(14)->toDateString(), 'days' => 3, 'adults' => 2, 'children' => 0,
            'category' => 'fnr', 'interests' => ['wildlife', 'waterfalls', 'boat'], 'fitness' => 2, 'tier' => 'mid',
            'transport' => 'tour', 'origin' => 'kampala', 'pace' => 'full', 'must_do' => [], 'budget_limit' => null,
        ];
    }

    public function build(array $raw): array
    {
        $cfg = config('murchison');
        $in = array_merge(self::defaults(), array_filter($raw, fn ($v) => $v !== null));
        $in['days'] = max(1, min(7, (int) $in['days']));
        $in['adults'] = max(1, (int) $in['adults']);
        $in['children'] = max(0, (int) $in['children']);
        $in['fitness'] = max(1, min(3, (int) $in['fitness']));
        $in['interests'] = array_values((array) $in['interests']);
        $in['must_do'] = array_values(array_unique((array) $in['must_do']));
        $n = $in['days'];
        $start = CarbonImmutable::parse($in['start_date'] ?: now()->addDays(14));
        [$seasonName, $seasonNote] = $cfg['months'][(int) $start->month];
        $wet = $seasonName === 'Wet';
        $origin = $cfg['origins'][$in['origin']] ?? $cfg['origins']['kampala'];
        $farOrigin = $origin['hours'] >= 2;

        $reasons = $skipped = $warnings = [];
        if ($n === 1 && $farOrigin) {
            $warnings[] = "A one-day visit from {$origin['label']} means about ".($origin['hours'] * 2 + 2).' hours on the road. At least 3 days (2 nights) is recommended.';
        }
        if ($wet) {
            $warnings[] = "{$start->format('F')} is in the rainy season: {$seasonNote} Pack a rain jacket and allow extra driving time.";
        }

        // 1. Score activities -------------------------------------------------------------
        $activities = Activity::with('place')->active()->get();
        $ranked = $activities->map(function (Activity $a) use ($in, $wet, &$skipped) {
            $r = $this->score($a, $in, $wet);
            if (! $r && $a->fitness_level > $in['fitness'] && array_intersect($a->interests ?? [], $in['interests'])) {
                $skipped[] = "{$a->name}: left out because it is more demanding than the fitness level you chose.";
            }

            return $r ? ['a' => $a] + $r : null;
        })->filter()->sortByDesc('score')->values();

        // 2. Place into the day × slot grid ----------------------------------------------
        $used = $count = [];
        for ($d = 1; $d <= $n; $d++) {
            $used[$d] = [];
            $count[$d] = 0;
        }
        // Travel in on day 1, travel out on the last day
        $used[1][] = 'dawn';
        if ($farOrigin) {
            $used[1][] = 'morning';
        }
        if ($n > 1) {
            array_push($used[$n], 'afternoon', 'evening', 'night');
        } else {
            $used[1][] = 'night';
        }

        $maxPerDay = $in['pace'] === 'relaxed' ? 2 : 3;
        $scheduled = [];
        foreach ($ranked as $r) {
            /** @var Activity $a */
            $a = $r['a'];
            $limit = $r['must'] ? $maxPerDay + 1 : $maxPerDay;
            // Spread activities: try the least-busy day first (stable for equal counts)
            $candidates = collect($this->placements($a, $n))->values()->map(fn ($p, $i) => [$p, $i])
                ->sortBy(fn ($x) => $a->place?->type === 'forest' ? $x[1] : [$count[$x[0][0]], $x[1]])->map(fn ($x) => $x[0])->values();
            $spot = $candidates->first(function ($p) use ($a, $used, $count, $limit) {
                [$d, $s] = $p;

                return $count[$d] < $limit && empty(array_intersect($this->occupied($a, $s), $used[$d]));
            });
            if (! $spot) {
                if ($r['must'] || $r['matched']) {
                    $skipped[] = "{$a->name}: there was no free time slot. Add a day to include it.";
                }
                continue;
            }
            [$d, $s] = $spot;
            $used[$d] = array_merge($used[$d], $this->occupied($a, $s));
            $count[$d]++;
            $scheduled[] = ['a' => $a, 'day' => $d, 'slot' => $s];
            $labels = collect($r['matched'])->map(fn ($i) => strtolower($cfg['interests'][$i] ?? $i))->take(2)->implode(' and ');
            $reasons[] = $r['must'] ? "{$a->name}: you asked for it." : ($labels ? "{$a->name}: matches your interest in {$labels}." : "{$a->name}: a signature Murchison Falls experience for first-time visitors.");
            if ($wet && $a->wet_season_note) {
                $warnings[] = "{$a->name}: {$a->wet_season_note}";
            }
            if ($a->min_age && $in['children']) {
                $warnings[] = "{$a->name}: minimum age is {$a->min_age} years; children below this age are excluded from the cost.";
            }
        }

        // 3. Day-by-day schedule ---------------------------------------------------------
        $days = [];
        $ferryDays = 0;
        $tier = $cfg['tiers'][$in['tier']] ?? $cfg['tiers']['mid'];
        for ($d = 1; $d <= $n; $d++) {
            $date = $start->addDays($d - 1);
            $items = [];
            $todays = collect($scheduled)->where('day', $d)->sortBy(fn ($x) => array_search($x['slot'], self::SLOT_ORDER))->values();

            if ($d === 1) {
                $items[] = ['time' => $farOrigin ? '06:00' : '07:00', 'kind' => 'travel', 'title' => "Depart {$origin['label']}",
                    'detail' => "Drive about {$origin['km']} km ({$origin['hours']} h) to ".str_replace('-', ' ', ucwords($origin['gate'], '-')).", pay UWA park entry at the gate (UWA accepts cards and mobile money at most gates), then continue to Paraa (≈{$cfg['gate_to_paraa_km']} km)."];
            }
            $crossed = false;
            foreach ($todays as $x) {
                /** @var Activity $a */
                $a = $x['a'];
                if ($a->needsFerry() && ! $crossed) {
                    $crossed = true;
                    $ferryDays++;
                    $items[] = ['time' => $x['slot'] === 'dawn' ? '06:00' : '', 'kind' => 'ferry', 'title' => 'Paraa ferry to the North Bank', 'detail' => 'Cross the Victoria Nile on the Paraa ferry. Check the current ferry schedule on the Travel Updates page.'];
                }
                $items[] = [
                    'time' => $cfg['slots'][$x['slot']], 'kind' => 'activity', 'title' => $a->name, 'activity' => $a->slug,
                    'place' => $a->place?->name, 'lat' => $a->place?->latitude, 'lng' => $a->place?->longitude,
                    'detail' => trim($a->description.' About '.rtrim(rtrim(number_format($a->hours, 1), '0'), '.').' h.'),
                    'cost_ugx' => $this->budget->activityCost($a, $in['adults'], $in['children'], $in['category']),
                    'has_360' => $a->scenes()->where('is_published', true)->exists(),
                ];
                if ($a->place?->tip) {
                    $items[] = ['time' => '', 'kind' => 'tip', 'title' => 'Tip', 'detail' => $a->place->tip];
                }
            }
            if ($d === $n && $n > 1) {
                $items[] = ['time' => '13:00', 'kind' => 'travel', 'title' => "Drive back to {$origin['label']}", 'detail' => "Exit the park and return (≈".($origin['km'] + $cfg['gate_to_paraa_km'])." km). Gates close at 19:00."];
            }
            if ($d < $n) {
                $items[] = ['time' => $todays->contains(fn ($x) => $x['slot'] === 'night') ? '21:30' : '19:00', 'kind' => 'stay', 'title' => "Overnight – {$tier['label']}", 'detail' => $tier['examples'].'.'];
            }
            if ($n === 1) {
                $items[] = ['time' => '17:30', 'kind' => 'travel', 'title' => "Return to {$origin['label']}", 'detail' => 'Leave before the gate closes (19:00).'];
            }
            $title = $d === 1 ? 'Arrival & first views' : ($d === $n ? 'Final morning & departure' : "Exploring the park (day {$d})");
            $days[] = ['day' => $d, 'date' => $date->toDateString(), 'label' => $date->format('D j M').' – '.$title, 'items' => $items];
        }

        // 4. Budget ----------------------------------------------------------------------
        $budget = $this->budget->calculate([
            'days' => $n, 'adults' => $in['adults'], 'children' => $in['children'], 'category' => $in['category'],
            'tier' => $in['tier'], 'transport' => $in['transport'], 'origin' => $in['origin'],
            'activities' => collect($scheduled)->map(fn ($x) => $x['a']->slug)->all(), 'ferry_days' => $ferryDays,
        ]);
        if (! empty($in['budget_limit']) && $budget['total'] > (int) $in['budget_limit']) {
            $warnings[] = 'The estimated total ('.ugx($budget['total']).') is above your budget of '.ugx((int) $in['budget_limit']).'. Try the budget tier, fewer days or a relaxed pace.';
        }

        // 5. Packing list + live alerts --------------------------------------------------
        $slugs = collect($scheduled)->map(fn ($x) => $x['a']->category)->all();
        $packing = ['Passport/national ID and yellow fever certificate', 'Sunscreen, hat and sunglasses', 'Insect repellent and neutral-coloured long sleeves (tsetse flies)', 'Malaria prophylaxis and a small first-aid kit', 'Binoculars and a camera with spare batteries', 'Cash or mobile money for fees and tips'];
        if (array_intersect($slugs, ['primates', 'hiking', 'nature'])) {
            $packing[] = 'Hiking boots, long trousers and gaiters for forest trails';
        }
        if (in_array('boat', $slugs) || in_array('fishing', $slugs)) {
            $packing[] = 'Light jacket and a waterproof bag for the boat';
        }
        if (in_array('fishing', $slugs)) {
            $packing[] = 'UWA sport-fishing permit (bought at Paraa) – catch and release only';
        }
        if ($wet) {
            $packing[] = 'Rain jacket and quick-drying clothes';
        }
        if (in_array('game_drive', $slugs)) {
            $packing[] = 'Warm layer for dawn and night game drives; torch/headlamp';
        }

        return [
            'input' => $in,
            'days' => $days,
            'budget' => $budget,
            'season' => ['month' => $start->format('F'), 'season' => $seasonName, 'note' => $seasonNote],
            'reasons' => $reasons,
            'skipped' => array_values(array_unique($skipped)),
            'warnings' => array_values(array_unique($warnings)),
            'packing' => $packing,
            'alerts' => TravelUpdate::current()->take(3)->get(['title', 'severity', 'category'])->toArray(),
            'activity_slugs' => collect($scheduled)->map(fn ($x) => $x['a']->slug)->values()->all(),
        ];
    }

    /** Score how well an activity suits the visitor; null = do not suggest. */
    public function score(Activity $a, array $in, bool $wet): ?array
    {
        $matched = array_values(array_intersect($a->interests ?? [], $in['interests']));
        $must = in_array($a->slug, $in['must_do'], true);
        if (! $must && ! $matched && $a->priority < 9) {
            return null;
        }
        if (! $must && $a->requires && ! array_intersect($a->requires, $in['interests'])) {
            return null;
        }
        if (! $must && $a->fitness_level > $in['fitness']) {
            return null;
        }
        $s = count($matched) * 10 + $a->priority * 2 + ($must ? 100 : 0);
        if ($wet && $a->wet_season_note) {
            $s -= 3;
        }
        if ($in['tier'] === 'budget' && ! $must && $a->priceUgx($in['category']) > 250000) {
            $s -= 15;
        }

        return ['score' => $s, 'matched' => $matched, 'must' => $must];
    }

    /** Candidate (day, slot) pairs in order of preference. */
    public function placements(Activity $a, int $n): array
    {
        $slots = $a->slots ?: ['morning'];
        $middle = $n > 2 ? range(2, $n - 1) : [];
        $out = [];
        // Follow the activity's own slot preference (e.g. boat cruise: afternoon before morning)
        $push = function (int $d, array $allowed) use (&$out, $slots) {
            foreach ($slots as $s) {
                if (in_array($s, $allowed, true)) {
                    $out[] = [$d, $s];
                }
            }
        };
        // Budongo/Kaniyo Pabidi is on the way in/out (south): prefer the first or last day
        $southern = $a->place?->type === 'forest';
        if ($southern) {
            if ($n > 1) {
                $push($n, ['dawn', 'morning']);
            }
            foreach ($middle as $d) {
                $push($d, ['dawn', 'morning']);
            }
            $push(1, ['afternoon']);

            return $out;
        }
        foreach ($middle as $d) {
            $push($d, self::SLOT_ORDER);
        }
        $push(1, ['afternoon', 'evening', 'morning', 'night']);
        if ($n > 1) {
            $push($n, ['dawn', 'morning']);
        }

        return $out;
    }

    /** Slots taken when the activity starts in $start (long activities spill into the next slot). */
    public function occupied(Activity $a, string $start): array
    {
        $i = array_search($start, self::SLOT_ORDER, true);
        if ($a->hours >= 4 && in_array($start, ['dawn', 'morning'], true)) {
            return array_slice(self::SLOT_ORDER, $i, 2);
        }
        if ($a->hours >= 3 && $start === 'afternoon') {
            return ['afternoon', 'evening'];
        }

        return [$start];
    }

    /** Activities for form checkboxes. */
    public function catalogue(): Collection
    {
        return Activity::with('place')->active()->orderByDesc('priority')->get();
    }
}

<?php

namespace App\Services\Murchison;

use Carbon\Carbon;

/**
 * The smart itinerary engine – the main feature of the system.
 *
 * 1. Every activity is scored for the visitor: +10 for each interest it matches, +2 × its importance for a
 *    first visit, +100 if the visitor marked it as a must-do. Activities above the visitor's fitness level,
 *    or that need a special interest the visitor did not choose, are left out with a reason.
 * 2. The best activities are placed into time slots (dawn, morning, afternoon, evening) on the days of the
 *    trip following the park's rules: dawn game drives on the northern bank, launch trips in the afternoon,
 *    the Top of the Falls on the arrival afternoon, chimpanzee tracking on the way in or out, and at most
 *    two (relaxed) or three (full pace) activities a day.
 * 3. Travel, overnight stays, tips, warnings, a packing list and the cost (TripCost) are added.
 */
class SmartItinerary
{
    public const SLOT_ORDER = ['dawn', 'morning', 'afternoon', 'evening'];

    public const SLOT_TIME = ['dawn' => '06:30', 'morning' => '09:00', 'afternoon' => '14:00', 'evening' => '16:30'];

    public static function defaults(): array
    {
        return [
            'start_date' => now()->addDays(14)->toDateString(), 'days' => 3, 'adults' => 2, 'children' => 0, 'residency' => 'fnr',
            'interests' => ['wildlife', 'waterfalls', 'boat'], 'fitness' => 2, 'tier' => 'mid', 'transport' => 'tour',
            'origin' => 'kampala', 'pace' => 'full', 'must_do' => [],
        ];
    }

    /** Cleans and completes the visitor's choices so the engine always receives valid input. */
    public static function normalize(array $in): array
    {
        $d = self::defaults();
        $acts = array_keys(config('murchison.activities'));
        $pick = fn ($v, array $allowed, $default) => in_array($v, $allowed, true) ? $v : $default;
        $date = $in['start_date'] ?? $d['start_date'];
        try {
            $date = Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            $date = $d['start_date'];
        }

        return [
            'start_date' => $date,
            'days' => min(6, max(1, (int) ($in['days'] ?? $d['days']))),
            'adults' => min(30, max(1, (int) ($in['adults'] ?? $d['adults']))),
            'children' => min(30, max(0, (int) ($in['children'] ?? $d['children']))),
            'residency' => $pick($in['residency'] ?? null, array_keys(config('murchison.residency')), $d['residency']),
            'interests' => array_values(array_intersect((array) ($in['interests'] ?? []), array_keys(config('murchison.interests')))),
            'fitness' => min(3, max(1, (int) ($in['fitness'] ?? $d['fitness']))),
            'tier' => $pick($in['tier'] ?? null, array_keys(config('murchison.tiers')), $d['tier']),
            'transport' => $pick($in['transport'] ?? null, array_keys(config('murchison.transport')), $d['transport']),
            'origin' => $pick($in['origin'] ?? null, array_keys(config('murchison.origins')), $d['origin']),
            'pace' => $pick($in['pace'] ?? null, ['relaxed', 'full'], $d['pace']),
            'must_do' => array_values(array_intersect((array) ($in['must_do'] ?? []), $acts)),
        ];
    }

    /** Short link code so an itinerary can be shared, printed or sent with a booking. */
    public static function encode(array $input): string
    {
        return rtrim(strtr(base64_encode(json_encode($input)), '+/', '-_'), '=');
    }

    public static function decode(?string $code): ?array
    {
        if (! $code) {
            return null;
        }
        $json = base64_decode(strtr($code, '-_', '+/'), true);
        $data = $json ? json_decode($json, true) : null;

        return is_array($data) ? self::normalize($data) : null;
    }

    private function score(string $id, array $a, array $in, bool $wet): ?array
    {
        $matched = array_values(array_intersect($a['interests'], $in['interests']));
        $must = in_array($id, $in['must_do'], true);
        if (! $must && ! $matched && $a['priority'] < 9) {
            return null;
        }
        if (! $must && isset($a['requires']) && ! array_intersect($a['requires'], $in['interests'])) {
            return null;
        }
        if (! $must && $a['fitness'] > $in['fitness']) {
            return null;
        }
        $s = count($matched) * 10 + $a['priority'] * 2 + ($must ? 100 : 0);
        if ($wet && isset($a['wet_note'])) {
            $s -= 3;
        }
        // Expensive extras are ranked lower on a budget trip.
        if ($in['tier'] === 'budget' && ! $must && TripCost::feeUgx($a['fee'], $in['residency']) > 200000) {
            $s -= 15;
        }

        return ['s' => $s, 'matched' => $matched, 'must' => $must];
    }

    /** Which (day, slot) pairs an activity may use, in order of preference. */
    private function placements(string $id, array $a, int $n, string $origin): array
    {
        $out = [];
        $middle = $n > 2 ? range(2, $n - 1) : [];
        $firstDay = $origin === 'masindi' ? ['morning', 'afternoon', 'evening'] : ['afternoon', 'evening'];
        $lastDay = $n === 1 ? [] : ['dawn', 'morning'];
        $push = function (int $d, array $slots) use (&$out, $a) {
            foreach ($a['slots'] as $s) {
                if (in_array($s, $slots, true)) {
                    $out[] = [$d, $s];
                }
            }
        };
        switch ($id) {
            case 'ziwa-rhinos':
                if ($origin !== 'masindi') {
                    $out[] = [1, 'morning'];
                    if ($n > 1) {
                        $out[] = [$n, 'afternoon'];
                    }
                }

                return $out;
            case 'chimps':
                if ($origin === 'masindi') {
                    $push(1, ['dawn', 'morning']);
                }
                if ($n > 1) {
                    $push($n, ['dawn', 'morning']);
                }
                foreach ($middle as $d) {
                    $push($d, ['dawn', 'morning']);
                }

                return $out;
            case 'top-falls':
                $push(1, ['afternoon', 'evening']);
                foreach ($middle as $d) {
                    $push($d, ['evening', 'afternoon']);
                }
                if ($n > 1) {
                    $out[] = [$n, 'morning'];
                }

                return $out;
            default:
                foreach ($middle as $d) {
                    $push($d, self::SLOT_ORDER);
                }
                $push(1, $firstDay);
                if ($n > 1) {
                    $push($n, $lastDay);
                }

                return $out;
        }
    }

    /** Slots an activity occupies when it starts in a slot (long activities run into the next one). */
    private function occupied(string $id, array $a, string $start, int $day): array
    {
        if ($id === 'ziwa-rhinos') {
            return $day === 1 ? ['morning', 'afternoon'] : [];
        }
        $i = array_search($start, self::SLOT_ORDER, true);
        if ($a['hours'] >= 4 && in_array($start, ['dawn', 'morning'], true)) {
            return array_slice(self::SLOT_ORDER, $i, max(1, min($i + 2, 2) - $i));
        }
        if ($a['hours'] >= 3 && $start === 'afternoon') {
            return ['afternoon', 'evening'];
        }

        return [$start];
    }

    public function build(array $raw): array
    {
        $in = self::normalize($raw);
        $n = $in['days'];
        $start = Carbon::parse($in['start_date']);
        $season = config('murchison.months.'.$start->month);
        $wet = $season[1] === 'Wet';
        $origin = config('murchison.origins.'.$in['origin']);
        $activities = config('murchison.activities');
        $attractions = config('murchison.attractions');
        $interests = config('murchison.interests');
        $reasons = $skipped = $warnings = [];

        if ($n === 1 && $in['origin'] !== 'masindi') {
            $warnings[] = "A one-day visit from {$origin['label']} means about ".($origin['hours_to_gate'] * 2 + 2).' hours on the road. We recommend at least 2 days (1 night).';
        }
        if ($wet) {
            $warnings[] = "{$season[0]} is in the rainy season. {$season[2]} Pack a rain jacket and allow extra driving time.";
        }

        // 1. Rank activities
        $ranked = [];
        foreach ($activities as $id => $a) {
            $r = $this->score($id, $a, $in, $wet);
            if (! $r) {
                if ($a['fitness'] > $in['fitness'] && array_intersect($a['interests'], $in['interests'])) {
                    $skipped[] = "{$a['name']}: left out because it is more demanding than the fitness level you chose.";
                }

                continue;
            }
            $ranked[] = ['id' => $id, 'a' => $a, 'r' => $r];
        }
        usort($ranked, fn ($x, $y) => $y['r']['s'] <=> $x['r']['s']);

        // 2. Fill a timetable greedily
        $maxPerDay = $in['pace'] === 'relaxed' ? 2 : 3;
        $used = $count = [];
        for ($d = 1; $d <= $n; $d++) {
            $used[$d] = [];
            $count[$d] = 0;
        }
        if ($in['origin'] !== 'masindi') {
            $used[1][] = 'dawn';
        }
        if ($n > 1) {
            array_push($used[$n], 'afternoon', 'evening');
        }
        if ($n === 1) {
            array_push($used[1], 'dawn', 'morning');
        }

        $scheduled = [];
        foreach ($ranked as $x) {
            ['id' => $id, 'a' => $a, 'r' => $r] = $x;
            $limit = $r['must'] ? $maxPerDay + 1 : $maxPerDay;
            $spot = null;
            foreach ($this->placements($id, $a, $n, $in['origin']) as [$d, $s]) {
                if ($count[$d] >= $limit) {
                    continue;
                }
                if ($id === 'ziwa-rhinos') {
                    $ok = $d === 1 ? ! in_array('morning', $used[1], true) && ! in_array('afternoon', $used[1], true) : $d === $n;
                } else {
                    $ok = ! array_intersect($this->occupied($id, $a, $s, $d), $used[$d]);
                }
                if ($ok) {
                    $spot = [$d, $s];
                    break;
                }
            }
            if (! $spot) {
                if ($r['must'] || $r['s'] >= 25) {
                    $skipped[] = "{$a['name']}: there was no free time for it. Add a day to include it.";
                }

                continue;
            }
            [$d, $s] = $spot;
            $used[$d] = array_merge($used[$d], $this->occupied($id, $a, $s, $d));
            $count[$d]++;
            $scheduled[] = ['id' => $id, 'a' => $a, 'day' => $d, 'slot' => $s];
            $labels = array_map(fn ($i) => strtolower($interests[$i][0]), $r['matched']);
            $reasons[] = $r['must'] ? "{$a['name']}: you asked for it."
                : ($labels ? "{$a['name']}: matches your interest in ".implode(' and ', array_slice($labels, 0, 2)).'.' : "{$a['name']}: one of the park's highlights for a first visit.");
            if ($wet && isset($a['wet_note'])) {
                $warnings[] = "{$a['name']}: {$a['wet_note']}";
            }
        }

        // 3. Day-by-day itinerary
        $tier = config('murchison.tiers.'.$in['tier']);
        $days = [];
        for ($d = 1; $d <= $n; $d++) {
            $date = $start->copy()->addDays($d - 1);
            $items = [];
            $todays = array_values(array_filter($scheduled, fn ($x) => $x['day'] === $d));
            $onWayHome = $d === $n && $n > 1 ? array_values(array_filter($todays, fn ($x) => $x['id'] === 'ziwa-rhinos')) : [];
            $todays = array_values(array_filter($todays, fn ($x) => ! in_array($x, $onWayHome, true)));
            usort($todays, fn ($x, $y) => array_search($x['slot'], self::SLOT_ORDER) <=> array_search($y['slot'], self::SLOT_ORDER));

            if ($d === 1 && $in['origin'] !== 'masindi') {
                $viaZiwa = collect($todays)->contains(fn ($x) => $x['id'] === 'ziwa-rhinos');
                $items[] = ['time' => '06:30', 'kind' => 'travel', 'title' => "Depart {$origin['label']}",
                    'detail' => 'Drive north through Luweero'.($viaZiwa ? ', stopping at Ziwa Rhino Sanctuary,' : '')." and Masindi to Kichumbanyobo Gate (about {$origin['km_to_gate']} km, {$origin['hours_to_gate']} hours of driving). Pay park entry at the gate."];
            }
            if ($d === 1 && $in['origin'] === 'masindi') {
                $items[] = ['time' => '07:00', 'kind' => 'travel', 'title' => 'Depart Masindi', 'detail' => 'Drive about 40 km (1 hour) to Kichumbanyobo Gate and pay park entry.'];
            }

            $add = function (array $x, ?string $time = null) use (&$items, $attractions, $in) {
                $a = $x['a'];
                $at = $attractions[$a['attraction']];
                $ferry = $at['zone'] === 'north' ? ' Cross the Nile on the Paraa ferry first.' : '';
                $items[] = ['time' => $time ?? ($x['id'] === 'chimps' ? '08:00' : self::SLOT_TIME[$x['slot']]), 'kind' => 'activity', 'title' => $a['name'],
                    'activity_id' => $x['id'], 'attraction_id' => $a['attraction'],
                    'detail' => $a['description'].$ferry." About {$a['hours']} hours.".($x['id'] === 'chimps' && $in['children'] ? ' Children under 12 cannot join chimpanzee tracking.' : ''),
                    'cost' => TripCost::activityCost($x['id'], $in['adults'], $in['children'], $in['residency'])];
                if ($at['tip']) {
                    $items[] = ['time' => '', 'kind' => 'tip', 'title' => 'Tip', 'detail' => $at['tip']];
                }
            };
            foreach ($todays as $x) {
                $add($x, $x['id'] === 'ziwa-rhinos' ? '10:30' : ($x['id'] === 'top-falls' && $d === 1 ? '16:30' : null));
            }

            if ($d === $n && $n > 1) {
                $late = collect($todays)->contains(fn ($x) => $x['slot'] === 'morning' || $x['id'] === 'chimps' || $x['a']['hours'] >= 4);
                $items[] = ['time' => $late ? '13:00' : '11:00', 'kind' => 'travel', 'title' => "Drive back to {$origin['label']}",
                    'detail' => 'Exit through Kichumbanyobo Gate and return via Masindi'.($onWayHome ? ', stopping at Ziwa Rhino Sanctuary' : '').' (about '.($origin['km_to_gate'] + config('murchison.gate_to_paraa_km')).' km). Expect to arrive in the evening.'];
                foreach ($onWayHome as $x) {
                    $add($x, '15:30');
                }
            }
            if ($d < $n) {
                $items[] = ['time' => '19:00', 'kind' => 'stay', 'title' => "Overnight: {$tier['label']} lodge", 'detail' => $tier['examples'].'.'];
            }
            if ($n === 1) {
                $items[] = ['time' => '17:30', 'kind' => 'travel', 'title' => "Return to {$origin['label']}", 'detail' => 'Leave the park before the gate closes (19:00) and drive back.'];
            }
            $title = $d === 1 ? 'Arrival and first views' : ($d === $n ? 'Final morning and departure' : "Exploring the park (day {$d})");
            $days[] = ['day' => $d, 'date' => $date->toDateString(), 'title' => $date->format('D j M').' – '.$title, 'items' => $items];
        }

        // 4. Budget
        $northDays = collect($scheduled)->filter(fn ($x) => $attractions[$x['a']['attraction']]['zone'] === 'north')->pluck('day')->unique()->count();
        $cost = TripCost::compute([
            'days' => $n, 'adults' => $in['adults'], 'children' => $in['children'], 'residency' => $in['residency'], 'tier' => $in['tier'],
            'transport' => $in['transport'], 'origin' => $in['origin'], 'activity_ids' => array_column($scheduled, 'id'), 'north_bank_days' => $northDays,
        ]);

        // 5. Packing list
        $ids = array_column($scheduled, 'id');
        $packing = ['Passport or national ID and yellow fever certificate', 'Sunscreen, hat and sunglasses', 'Insect repellent and light-coloured long sleeves (tsetse flies)',
            'Malaria tablets and a small first-aid kit', 'Binoculars and a camera with spare batteries', 'Cash or mobile money for fees and tips'];
        if (array_intersect($ids, ['chimps', 'rabongo-walk', 'hike-falls'])) {
            $packing[] = 'Hiking boots and long trousers for forest trails';
        }
        if (array_intersect($ids, ['launch', 'delta'])) {
            $packing[] = 'A light jacket for the boat and a waterproof bag for your phone';
        }
        if ($wet) {
            $packing[] = 'Rain jacket and quick-drying clothes';
        }
        if (array_intersect($ids, ['game-drive', 'balloon'])) {
            $packing[] = 'A warm layer for the early-morning start';
        }

        return [
            'input' => $in, 'code' => self::encode($in), 'days' => $days, 'costs' => $cost['lines'], 'total' => $cost['total'],
            'season' => ['month' => $season[0], 'season' => $season[1], 'note' => $season[2]],
            'reasons' => $reasons, 'skipped' => $skipped, 'warnings' => $warnings, 'packing' => $packing,
            'activity_count' => count($scheduled), 'attraction_ids' => array_values(array_unique(array_column(array_column($scheduled, 'a'), 'attraction'))),
        ];
    }

    /** One-line description of a plan, e.g. for bookings. */
    public function summary(array $plan): string
    {
        $acts = collect($plan['days'])->flatMap(fn ($d) => $d['items'])->where('kind', 'activity')->pluck('title')->implode(', ');

        return "{$plan['input']['days']} day(s) from {$plan['input']['start_date']}: {$acts}";
    }
}

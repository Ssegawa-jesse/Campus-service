<?php

namespace App\Services\Murchison;

use App\Models\Activity;
use Illuminate\Support\Collection;

/**
 * Travel Budgeting module.
 * Computes a full trip cost using the UWA three-tier visitor categories:
 *   fnr = Foreign Non-Resident (USD), fr = Foreign Resident (USD), eac = East African Citizen (UGX).
 * Everything is totalled in UGX and also shown in USD.
 */
class BudgetCalculator
{
    /**
     * @param  array{days:int,adults:int,children:int,category:string,tier:string,transport:string,origin:string,activities:array<int,string>,ferry_days?:int|null}  $in
     * @return array{lines:array<int,array{label:string,detail:string,ugx:int}>,subtotal:int,contingency:int,total:int,total_usd:int,per_person:int,currency:string,rate:float}
     */
    public function calculate(array $in): array
    {
        $cfg = config('murchison');
        $days = max(1, min(14, (int) $in['days']));
        $nights = max(0, $days - 1);
        $adults = max(1, (int) $in['adults']);
        $children = max(0, (int) ($in['children'] ?? 0));
        $cat = array_key_exists($in['category'] ?? '', $cfg['categories']) ? $in['category'] : 'fnr';
        $tier = $cfg['tiers'][$in['tier'] ?? 'mid'] ?? $cfg['tiers']['mid'];
        $transport = $in['transport'] ?? 'tour';
        $origin = $cfg['origins'][$in['origin'] ?? 'kampala'] ?? $cfg['origins']['kampala'];
        $paying = $adults + $children * $cfg['child_share'];
        $persons = $adults + $children;

        /** @var Collection<int,Activity> $acts */
        $acts = Activity::with('place')->whereIn('slug', $in['activities'] ?? [])->get()->keyBy('slug');
        $chosen = collect($in['activities'] ?? [])->map(fn ($s) => $acts[$s] ?? null)->filter();

        $lines = [];
        $entry = $this->entryUgx($cat);
        $lines[] = ['key' => 'entry', 'label' => 'Park entry (UWA)', 'detail' => "{$days} day(s) × {$adults} adult(s)".($children ? " + {$children} child(ren) at half rate" : '').' @ '.$this->entryLabel($cat), 'ugx' => (int) round($entry * $paying * $days)];

        $actTotal = 0;
        $actDetail = [];
        foreach ($chosen as $a) {
            $cost = $this->activityCost($a, $adults, $children, $cat);
            $actTotal += $cost;
            $actDetail[] = $a->name.' ('.$a->priceLabel($cat).'/person'.($a->group_fee_usd > 0 || $a->group_fee_ugx > 0 ? ' + guide' : '').')';
        }
        $lines[] = ['key' => 'activities', 'label' => 'Activity permits', 'detail' => $actDetail ? implode('; ', $actDetail) : 'No activities selected', 'ugx' => $actTotal];

        $lines[] = ['key' => 'accommodation', 'label' => 'Accommodation', 'detail' => "{$nights} night(s), {$tier['label']} (≈US\${$tier['usd_per_person_night']} pp/night, children half)", 'ugx' => usd_to_ugx($tier['usd_per_person_night'] * $paying * $nights)];
        if ($tier['meals_usd_per_person_day'] > 0) {
            $lines[] = ['key' => 'meals', 'label' => 'Meals', 'detail' => "{$days} day(s) × {$persons} person(s)", 'ugx' => usd_to_ugx($tier['meals_usd_per_person_day'] * $persons * $days)];
        }

        if ($transport !== 'arranged') {
            $km = ($origin['km'] + $cfg['gate_to_paraa_km']) * 2 + $cfg['park_km_per_day'] * $days;
            if ($transport === 'tour') {
                $lines[] = ['key' => 'transport', 'label' => '4x4 vehicle & driver-guide', 'detail' => "{$days} day(s) × US\${$cfg['transport']['tour']['usd_per_day']} (fuel included, ≈{$km} km)", 'ugx' => usd_to_ugx($cfg['transport']['tour']['usd_per_day'] * $days)];
            } else {
                $lines[] = ['key' => 'transport', 'label' => 'Fuel (self-drive)', 'detail' => "≈{$km} km from {$origin['label']} and around the park", 'ugx' => $km * $cfg['transport']['self']['ugx_per_km']];
            }
            $ferryDays = $in['ferry_days'] ?? ($chosen->contains(fn (Activity $a) => $a->needsFerry()) ? 1 : 0);
            $lines[] = ['key' => 'vehicle', 'label' => 'Vehicle entry & Paraa ferry', 'detail' => "Vehicle entry {$days} day(s)".($ferryDays ? ' + '.($ferryDays * 2).' ferry crossing(s)' : ''), 'ugx' => $cfg['vehicle_entry_per_day_ugx'] * $days + $cfg['ferry_per_crossing_ugx'] * $ferryDays * 2];
        }

        $subtotal = array_sum(array_column($lines, 'ugx'));
        $contingency = (int) round($subtotal * 0.10);
        $total = $subtotal + $contingency;
        $rate = (float) setting('usd_rate', config('pearl360.usd_rate'));

        return [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'contingency' => $contingency,
            'total' => $total,
            'total_usd' => (int) round($total / max(1, $rate)),
            'per_person' => (int) round($total / max(1, $persons)),
            'category' => $cat,
            'category_label' => $cfg['categories'][$cat]['label'],
            'rate' => $rate,
        ];
    }

    /** Cost of one activity for the group. Children under the minimum age (e.g. chimps 12+) are excluded. */
    public function activityCost(Activity $a, int $adults, int $children, string $cat): int
    {
        $kids = $a->min_age >= 12 ? 0 : $children;

        return $a->priceUgx($cat) * ($adults + $kids) + $a->groupFeeUgx($cat);
    }

    public function entryUgx(string $cat): int
    {
        $fee = config("murchison.entry.$cat");

        return $cat === 'eac' ? (int) $fee : usd_to_ugx($fee);
    }

    public function entryLabel(string $cat): string
    {
        $fee = config("murchison.entry.$cat");

        return $cat === 'eac' ? 'UGX '.number_format($fee) : 'US$'.$fee;
    }
}

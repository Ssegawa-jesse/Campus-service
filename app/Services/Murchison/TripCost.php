<?php

namespace App\Services\Murchison;

/**
 * Cost rules shared by the smart itinerary and the travel budget calculator,
 * so both always give the same figures. All amounts are worked out in Uganda shillings.
 */
class TripCost
{
    public static function rate(): int
    {
        return (int) config('murchison.ugx_per_usd');
    }

    /** A fee for the visitor's category (fnr/fr in US$, eac in UGX) converted to UGX. */
    public static function feeUgx(array $fee, string $residency): float
    {
        return $residency === 'eac' ? (float) $fee['eac'] : (float) $fee[$residency] * self::rate();
    }

    /** Cost of one activity for the group. Children under 12 cannot track chimpanzees, so they are not charged. */
    public static function activityCost(string $id, int $adults, int $children, string $residency): float
    {
        $a = config("murchison.activities.$id");
        if (! $a) {
            return 0;
        }
        $kids = $id === 'chimps' ? 0 : $children;

        return self::feeUgx($a['fee'], $residency) * ($adults + $kids) + (isset($a['group_fee']) ? self::feeUgx($a['group_fee'], $residency) : 0);
    }

    public static function needsFerry(string $activityId): bool
    {
        $att = config('murchison.activities.'.$activityId.'.attraction');

        return $att && config("murchison.attractions.$att.zone") === 'north';
    }

    /**
     * @param  array{days:int,adults:int,children:int,residency:string,tier:string,transport:string,origin:string,activity_ids:array,north_bank_days?:int|null}  $b
     * @return array{lines: array<int, array{label:string, detail:string, ugx:float}>, total: float}
     */
    public static function compute(array $b): array
    {
        $n = max(1, (int) $b['days']);
        $nights = max(0, $n - 1);
        $adults = max(1, (int) $b['adults']);
        $children = max(0, (int) $b['children']);
        $persons = $adults + $children;
        $paying = $adults + $children * config('murchison.fees.child_share');
        $tier = config('murchison.tiers.'.$b['tier']);
        $rate = self::rate();
        $lines = [];

        $lines[] = ['label' => 'Park entry', 'detail' => "{$n} day(s) × {$adults} adult(s)".($children ? " + {$children} child(ren) at half price" : ''),
            'ugx' => self::feeUgx(config('murchison.fees.entry_per_day'), $b['residency']) * $paying * $n];

        $ids = array_values($b['activity_ids']);
        $act = array_sum(array_map(fn ($id) => self::activityCost($id, $adults, $children, $b['residency']), $ids));
        $lines[] = ['label' => 'Activities', 'detail' => count($ids).' '.(count($ids) === 1 ? 'activity' : 'activities'), 'ugx' => $act];

        $lines[] = ['label' => 'Accommodation', 'detail' => "{$nights} night(s), ".strtolower($tier['label']).' (children at half rate)',
            'ugx' => $tier['per_person_night_usd'] * $rate * $paying * $nights];
        if ($tier['meals_per_person_day_usd']) {
            $lines[] = ['label' => 'Meals', 'detail' => "{$n} day(s) for {$persons} person(s)", 'ugx' => $tier['meals_per_person_day_usd'] * $rate * $persons * $n];
        }

        if ($b['transport'] !== 'arranged') {
            $origin = config('murchison.origins.'.$b['origin']);
            $km = ($origin['km_to_gate'] + config('murchison.gate_to_paraa_km')) * 2 + config('murchison.park_driving_km_per_day') * $n;
            if ($b['transport'] === 'tour') {
                $perDay = config('murchison.tour_vehicle_per_day_usd');
                $lines[] = ['label' => 'Vehicle and driver-guide', 'detail' => "{$n} day(s) × US\${$perDay}", 'ugx' => $perDay * $rate * $n];
            } else {
                $lines[] = ['label' => 'Fuel', 'detail' => "About {$km} km of driving", 'ugx' => $km * config('murchison.fuel_ugx_per_km')];
            }
            $north = $b['north_bank_days'] ?? (collect($ids)->contains(fn ($id) => self::needsFerry($id)) ? 1 : 0);
            $lines[] = ['label' => 'Vehicle entry and ferry', 'detail' => "Vehicle entry for {$n} day(s)".($north ? ' and '.($north * 2).' ferry crossing(s)' : ''),
                'ugx' => config('murchison.fees.vehicle_entry_per_day_ugx') * $n + config('murchison.fees.ferry_per_vehicle_ugx') * $north * 2];
        }

        $subtotal = array_sum(array_column($lines, 'ugx'));
        $lines[] = ['label' => 'Contingency (10%)', 'detail' => 'Tips, drinks, souvenirs and price changes', 'ugx' => $subtotal * 0.1];

        return ['lines' => $lines, 'total' => $subtotal * 1.1];
    }

    public static function ugx(float $n): string
    {
        return 'UGX '.number_format(round($n / 1000) * 1000);
    }

    public static function usd(float $ugx): string
    {
        return 'US$'.number_format(round($ugx / self::rate()));
    }
}

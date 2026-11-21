<?php

namespace App\Services\Murchison;

use App\Models\Place;

/**
 * Smart Navigation helper: straight-line (haversine) distance between two places,
 * converted to an estimated road distance and travel time for park tracks.
 * Crossing the Victoria Nile between the south and north banks adds the Paraa ferry.
 */
class RouteService
{
    public const ROAD_FACTOR = 1.35;   // park tracks are not straight
    public const PARK_SPEED_KMH = 30;  // UWA speed limit inside the park is 40 km/h; 30 average on murram
    public const FERRY_MINUTES = 30;   // waiting + crossing at Paraa

    public function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /** @return array{from:string,to:string,straight_km:float,road_km:float,minutes:int,ferry:bool,summary:string} */
    public function route(Place $from, Place $to): array
    {
        $straight = $this->haversineKm($from->latitude, $from->longitude, $to->latitude, $to->longitude);
        $road = $straight * self::ROAD_FACTOR;
        $ferry = ($from->zone === 'north') !== ($to->zone === 'north');
        $minutes = (int) round($road / self::PARK_SPEED_KMH * 60) + ($ferry ? self::FERRY_MINUTES : 0);

        return [
            'from' => $from->name,
            'to' => $to->name,
            'straight_km' => round($straight, 1),
            'road_km' => round($road, 1),
            'minutes' => $minutes,
            'ferry' => $ferry,
            'summary' => sprintf('About %s km by road, roughly %s%s.', round($road), $this->duration($minutes), $ferry ? ' including the Paraa ferry crossing' : ''),
        ];
    }

    public function duration(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return trim(($h ? "{$h} h " : '').($m ? "{$m} min" : ''));
    }
}

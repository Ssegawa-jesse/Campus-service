<?php

/*
|--------------------------------------------------------------------------
| Murchison Falls National Park – planning constants
|--------------------------------------------------------------------------
| Fees are INDICATIVE planning figures based on the published UWA tariff structure
| (Foreign Non-Resident & Foreign Resident in USD, East African Citizen in UGX).
| Confirm the current UWA tariff before travelling and update here, or edit activity
| prices in Admin → Activities & UWA prices.
*/

return [
    'park_slug' => 'murchison-falls-national-park',
    'centre' => ['lat' => 2.21, 'lng' => 31.75],
    'weather_point' => ['lat' => 2.284, 'lng' => 31.566, 'label' => 'Paraa (Victoria Nile)'],

    'categories' => [
        'fnr' => ['label' => 'Foreign Non-Resident', 'currency' => 'USD'],
        'fr' => ['label' => 'Foreign Resident', 'currency' => 'USD'],
        'eac' => ['label' => 'East African Citizen', 'currency' => 'UGX'],
    ],

    // Park entry per adult per 24 hours
    'entry' => ['fnr' => 45, 'fr' => 35, 'eac' => 25000],
    'child_share' => 0.5,                 // children (5–15 yrs) pay about half
    'vehicle_entry_per_day_ugx' => 30000, // saloon / 4x4 entry
    'ferry_per_crossing_ugx' => 25000,    // Paraa ferry, per vehicle per crossing

    'tiers' => [
        'budget' => ['label' => 'Budget', 'usd_per_person_night' => 35, 'meals_usd_per_person_day' => 20, 'examples' => 'UWA Paraa bandas, Red Chilli Rest Camp, campsites'],
        'mid' => ['label' => 'Mid-range', 'usd_per_person_night' => 140, 'meals_usd_per_person_day' => 0, 'examples' => 'Mid-range lodges near Paraa and the southern bank (full board)'],
        'luxury' => ['label' => 'Luxury', 'usd_per_person_night' => 380, 'meals_usd_per_person_day' => 0, 'examples' => 'Luxury Nile-side lodges with full board'],
    ],

    'transport' => [
        'tour' => ['label' => '4x4 with driver-guide (hired)', 'usd_per_day' => 150],
        'self' => ['label' => 'Self-drive (own vehicle)', 'ugx_per_km' => 650],
        'arranged' => ['label' => 'Already arranged / in a package'],
    ],

    'origins' => [
        'kampala' => ['label' => 'Kampala', 'km' => 255, 'hours' => 4.5, 'gate' => 'kichumbanyobo-gate'],
        'entebbe' => ['label' => 'Entebbe (airport)', 'km' => 295, 'hours' => 5.5, 'gate' => 'kichumbanyobo-gate'],
        'masindi' => ['label' => 'Masindi', 'km' => 40, 'hours' => 1, 'gate' => 'kichumbanyobo-gate'],
        'gulu' => ['label' => 'Gulu (north)', 'km' => 80, 'hours' => 1.5, 'gate' => 'wankwar-gate'],
        'hoima' => ['label' => 'Hoima (west)', 'km' => 110, 'hours' => 2.5, 'gate' => 'bugungu-gate'],
    ],
    'gate_to_paraa_km' => 45,
    'park_km_per_day' => 60,

    'interests' => [
        'wildlife' => 'Big game (lions, elephants, giraffes)',
        'waterfalls' => 'The falls and the Nile',
        'boat' => 'Boat cruises',
        'chimps' => 'Chimpanzees (Budongo)',
        'birds' => 'Birding (shoebill)',
        'hiking' => 'Walking and hiking',
        'fishing' => 'Sport fishing',
        'night' => 'Nocturnal wildlife',
        'photography' => 'Photography',
    ],

    'slots' => ['dawn' => '06:30', 'morning' => '09:00', 'afternoon' => '14:00', 'evening' => '16:30', 'night' => '19:30'],

    // General pattern for the Murchison area
    'months' => [
        1 => ['Dry', 'Hot and dry; excellent game viewing as animals gather near water.'],
        2 => ['Dry', 'Hot and dry; one of the best months for game drives.'],
        3 => ['Wet', 'Rains begin; landscapes turn green.'],
        4 => ['Wet', 'Wettest period; some northern tracks can be muddy.'],
        5 => ['Wet', 'Rainy; fewer visitors and lush scenery.'],
        6 => ['Dry', 'Dry season begins; very good for all activities.'],
        7 => ['Dry', 'Dry and popular; book lodges early.'],
        8 => ['Dry', 'Mostly dry; good wildlife viewing.'],
        9 => ['Wet', 'Short rains may start; boat trips remain excellent.'],
        10 => ['Wet', 'Short rains; afternoon showers common.'],
        11 => ['Wet', 'Short rains continue; excellent birding (migrants).'],
        12 => ['Dry', 'Dry season returns; busy holiday period.'],
    ],
];

<?php

/*
|--------------------------------------------------------------------------
| Murchison Falls National Park – planning data
|--------------------------------------------------------------------------
| Everything the smart itinerary, budget, map, assistant and travel updates use.
| Fees are INDICATIVE planning figures: confirm the current tariff with the Uganda
| Wildlife Authority (UWA) and update the numbers here when it changes.
| fnr / fr fees are in US$, eac fees are in Uganda shillings.
*/

return [
    'park_slug' => 'murchison-falls-national-park',
    'name' => 'Murchison Falls National Park',
    'area_km2' => 3893,
    'centre' => ['lat' => 2.2, 'lng' => 31.75],
    'weather_point' => ['lat' => 2.284, 'lng' => 31.566, 'label' => 'Paraa (Victoria Nile)'],
    'summary' => "Uganda's largest national park, where the Victoria Nile forces its way through a 7-metre gap and plunges 43 metres at Murchison Falls before flowing into Lake Albert. The park is known for game drives on the northern savannah, boat trips to the foot of the falls, the shoebill of the Nile delta and chimpanzees in the Budongo forest.",

    'ugx_per_usd' => 3700,

    'residency' => [
        'fnr' => 'Foreign non-resident',
        'fr' => 'Foreign resident (living in East Africa)',
        'eac' => 'East African citizen',
    ],

    'interests' => [
        'wildlife' => ['Big game (lions, elephants, giraffes)', '🦁'],
        'waterfalls' => ['The falls and the Nile', '💧'],
        'boat' => ['Boat cruises', '🛥️'],
        'chimps' => ['Chimpanzees and primates', '🐒'],
        'birds' => ['Birding (shoebill)', '🦅'],
        'hiking' => ['Walking and hiking', '🥾'],
        'photography' => ['Photography', '📷'],
        'fishing' => ['Sport fishing', '🎣'],
        'adventure' => ['Special experiences (balloon)', '🎈'],
        'culture' => ['Rhinos and communities on the way', '🦏'],
    ],

    // Positions are approximate and meant for orientation on the map, not for navigation.
    'attractions' => [
        'top-of-falls' => ['name' => 'Top of the Falls', 'category' => 'Waterfall', 'zone' => 'south', 'lat' => 2.2786, 'lng' => 31.6857,
            'description' => 'Viewpoint where the whole Nile squeezes through a narrow rock gorge and thunders down. Short trails lead along the gorge to several viewpoints.',
            'tip' => 'Late afternoon light is best for photographs. Wear shoes with grip: the rocks are wet from the spray.'],
        'paraa' => ['name' => 'Paraa and the ferry crossing', 'category' => 'River', 'zone' => 'river', 'lat' => 2.2843, 'lng' => 31.5655,
            'description' => 'The heart of the park on the Victoria Nile. Boat trips start from the Paraa jetty and the ferry takes vehicles to the northern bank for game drives.',
            'tip' => 'Arrive early for the first ferry if you plan a morning game drive.'],
        'bottom-of-falls' => ['name' => 'Bottom of the Falls (by boat)', 'category' => 'River', 'zone' => 'river', 'lat' => 2.2795, 'lng' => 31.6780,
            'description' => 'Destination of the launch trip from Paraa, passing hippos, crocodiles, elephants and many birds on the river banks.', 'tip' => null],
        'buligi' => ['name' => 'Buligi game tracks (north bank)', 'category' => 'Wildlife', 'zone' => 'north', 'lat' => 2.2650, 'lng' => 31.4700,
            'description' => "Open savannah between the Victoria and Albert Nile with the park's best game viewing: lions, elephants, Rothschild's giraffes, buffaloes, Uganda kobs and hartebeest.",
            'tip' => 'Go at dawn, when predators are most active. A UWA ranger guide helps you find the lions.'],
        'delta' => ['name' => 'Nile–Lake Albert delta', 'category' => 'River', 'zone' => 'river', 'lat' => 2.2700, 'lng' => 31.4200,
            'description' => 'Papyrus swamps where the Nile meets Lake Albert, one of the best places in Uganda to see the rare shoebill.',
            'tip' => 'Morning cruises give the best chance of seeing the shoebill.'],
        'kaniyo-pabidi' => ['name' => 'Kaniyo Pabidi (Budongo Forest)', 'category' => 'Forest', 'zone' => 'south', 'lat' => 1.9200, 'lng' => 31.7200,
            'description' => 'Tropical forest in the south of the conservation area where habituated chimpanzees can be tracked, together with forest birds and other primates.', 'tip' => null],
        'rabongo' => ['name' => 'Rabongo Forest', 'category' => 'Forest', 'zone' => 'south', 'lat' => 2.0900, 'lng' => 31.8600,
            'description' => 'Small riverine forest with nature walks, black-and-white colobus monkeys and forest birds.', 'tip' => null],
        'karuma' => ['name' => 'Karuma Falls', 'category' => 'Waterfall', 'zone' => 'route', 'lat' => 2.2470, 'lng' => 32.2440,
            'description' => 'Rapids on the Victoria Nile on the eastern edge of the conservation area, near the Kampala–Gulu highway.', 'tip' => null],
        'kichumbanyobo' => ['name' => 'Kichumbanyobo Gate', 'category' => 'Gate', 'zone' => 'south', 'lat' => 1.9700, 'lng' => 31.7700,
            'description' => 'Main southern entrance for visitors coming from Kampala through Masindi.', 'tip' => null],
        'tangi' => ['name' => 'Tangi Gate', 'category' => 'Gate', 'zone' => 'north', 'lat' => 2.2700, 'lng' => 32.0400,
            'description' => 'Eastern entrance near Karuma, used by visitors coming along the Kampala–Gulu highway.', 'tip' => null],
        'masindi' => ['name' => 'Masindi town', 'category' => 'Town', 'zone' => 'route', 'lat' => 1.6744, 'lng' => 31.7150,
            'description' => 'Last main town before the southern gate, with fuel, banks, mobile money agents and supermarkets.', 'tip' => null],
        'ziwa' => ['name' => 'Ziwa Rhino Sanctuary', 'category' => 'On the way', 'zone' => 'route', 'lat' => 1.4700, 'lng' => 32.0700,
            'description' => 'Sanctuary on the Kampala–Masindi route where southern white rhinos are tracked on foot. A popular stop on the way to the park.', 'tip' => null],
    ],

    // slots: dawn | morning | afternoon | evening (in order of preference); fitness 1 easy – 3 demanding.
    'activities' => [
        'launch' => ['name' => 'Launch trip to the bottom of the falls', 'attraction' => 'bottom-of-falls', 'interests' => ['boat', 'waterfalls', 'wildlife', 'birds', 'photography'],
            'slots' => ['afternoon', 'morning'], 'hours' => 3, 'fitness' => 1, 'fee' => ['fnr' => 35, 'fr' => 30, 'eac' => 30000], 'priority' => 10,
            'description' => 'Three-hour boat trip from Paraa up the Nile to the foot of the falls, with hippos, crocodiles, elephants and birds along the banks.'],
        'game-drive' => ['name' => 'Game drive on the Buligi tracks', 'attraction' => 'buligi', 'interests' => ['wildlife', 'photography'],
            'slots' => ['dawn'], 'hours' => 4, 'fitness' => 1, 'fee' => ['fnr' => 0, 'fr' => 0, 'eac' => 0], 'group_fee' => ['fnr' => 20, 'fr' => 20, 'eac' => 20000], 'priority' => 10,
            'wet_note' => 'Some tracks can be muddy in the rainy season; a 4x4 is recommended.',
            'description' => 'Early-morning drive across the northern savannah with a UWA ranger guide to find lions, elephants, giraffes and antelopes.'],
        'top-falls' => ['name' => 'Visit and walk at the Top of the Falls', 'attraction' => 'top-of-falls', 'interests' => ['waterfalls', 'hiking', 'photography'],
            'slots' => ['afternoon', 'evening'], 'hours' => 2, 'fitness' => 1, 'fee' => ['fnr' => 15, 'fr' => 10, 'eac' => 10000], 'priority' => 9,
            'description' => 'Walk along the gorge to the viewpoints above the falls.'],
        'hike-falls' => ['name' => 'Hike from the bottom to the top of the falls', 'attraction' => 'top-of-falls', 'interests' => ['hiking', 'waterfalls', 'photography'], 'requires' => ['hiking'],
            'slots' => ['afternoon'], 'hours' => 1.5, 'fitness' => 2, 'fee' => ['fnr' => 15, 'fr' => 10, 'eac' => 10000], 'priority' => 5,
            'wet_note' => 'The trail is slippery after rain.',
            'description' => 'Get off the launch at the foot of the falls and climb the trail to the top with a ranger (arrange in advance).'],
        'delta' => ['name' => 'Delta cruise for the shoebill', 'attraction' => 'delta', 'interests' => ['birds', 'boat', 'photography'],
            'slots' => ['dawn', 'morning'], 'hours' => 4, 'fitness' => 1, 'fee' => ['fnr' => 40, 'fr' => 35, 'eac' => 40000], 'priority' => 6,
            'description' => 'Boat cruise downstream from Paraa to the papyrus delta at Lake Albert in search of the shoebill and other water birds.'],
        'chimps' => ['name' => 'Chimpanzee tracking at Kaniyo Pabidi', 'attraction' => 'kaniyo-pabidi', 'interests' => ['chimps', 'hiking', 'photography'], 'requires' => ['chimps'],
            'slots' => ['dawn', 'morning'], 'hours' => 4, 'fitness' => 2, 'fee' => ['fnr' => 90, 'fr' => 70, 'eac' => 80000], 'priority' => 7,
            'wet_note' => 'Forest trails are muddy in the rainy season; wear boots.',
            'description' => 'Walk through Budongo forest with a guide to find a habituated chimpanzee community.'],
        'rabongo-walk' => ['name' => 'Nature walk in Rabongo Forest', 'attraction' => 'rabongo', 'interests' => ['hiking', 'birds', 'chimps'],
            'slots' => ['morning', 'afternoon'], 'hours' => 2.5, 'fitness' => 1, 'fee' => ['fnr' => 15, 'fr' => 10, 'eac' => 10000], 'priority' => 3,
            'description' => 'Guided walk in a riverine forest with colobus monkeys and forest birds.'],
        'birding' => ['name' => 'Guided birding walk', 'attraction' => 'paraa', 'interests' => ['birds', 'photography'],
            'slots' => ['dawn', 'morning'], 'hours' => 3, 'fitness' => 1, 'fee' => ['fnr' => 0, 'fr' => 0, 'eac' => 0], 'group_fee' => ['fnr' => 20, 'fr' => 20, 'eac' => 20000], 'priority' => 3,
            'description' => 'Morning walk with a birding guide around Paraa and the river banks.'],
        'fishing' => ['name' => 'Sport fishing below the falls', 'attraction' => 'bottom-of-falls', 'interests' => ['fishing'], 'requires' => ['fishing'],
            'slots' => ['morning', 'afternoon'], 'hours' => 4, 'fitness' => 2, 'fee' => ['fnr' => 50, 'fr' => 40, 'eac' => 50000], 'priority' => 2,
            'description' => 'Catch-and-release fishing for Nile perch with a licensed guide (permit required).'],
        'balloon' => ['name' => 'Hot-air balloon safari', 'attraction' => 'buligi', 'interests' => ['adventure', 'wildlife', 'photography'], 'requires' => ['adventure'],
            'slots' => ['dawn'], 'hours' => 3, 'fitness' => 1, 'fee' => ['fnr' => 380, 'fr' => 380, 'eac' => 1400000], 'priority' => 2,
            'description' => 'Sunrise flight over the northern savannah, where available. Book well in advance.'],
        'ziwa-rhinos' => ['name' => 'Rhino tracking at Ziwa Rhino Sanctuary', 'attraction' => 'ziwa', 'interests' => ['culture', 'wildlife', 'photography'], 'requires' => ['culture'],
            'slots' => ['morning', 'afternoon'], 'hours' => 2.5, 'fitness' => 1, 'fee' => ['fnr' => 50, 'fr' => 40, 'eac' => 40000], 'priority' => 4,
            'description' => 'Track southern white rhinos on foot with a ranger, on the way to or from the park.'],
    ],

    'fees' => [
        'entry_per_day' => ['fnr' => 45, 'fr' => 35, 'eac' => 25000], // per adult per 24 hours
        'child_share' => 0.5,
        'vehicle_entry_per_day_ugx' => 30000,
        'ferry_per_vehicle_ugx' => 25000, // per crossing at Paraa
    ],

    'tiers' => [
        'budget' => ['label' => 'Budget', 'per_person_night_usd' => 35, 'meals_per_person_day_usd' => 20, 'examples' => 'UWA bandas, campsites and budget lodges near Paraa and the southern gate'],
        'mid' => ['label' => 'Mid-range', 'per_person_night_usd' => 140, 'meals_per_person_day_usd' => 0, 'examples' => 'Mid-range lodges near Paraa and on the southern bank (meals usually included)'],
        'luxury' => ['label' => 'Luxury', 'per_person_night_usd' => 380, 'meals_per_person_day_usd' => 0, 'examples' => 'Luxury lodges along the Nile with full board'],
    ],

    'transport' => [
        'tour' => ['label' => '4x4 with driver-guide (hired)', 'note' => 'About US$150 a day including fuel and the driver.'],
        'self' => ['label' => 'Self-drive (own vehicle)', 'note' => 'Fuel estimated from the distance driven.'],
        'arranged' => ['label' => 'Already arranged / included in a package', 'note' => 'No transport cost added.'],
    ],
    'tour_vehicle_per_day_usd' => 150,
    'fuel_ugx_per_km' => 650,

    'origins' => [
        'kampala' => ['label' => 'Kampala', 'km_to_gate' => 255, 'hours_to_gate' => 4.5],
        'entebbe' => ['label' => 'Entebbe (airport)', 'km_to_gate' => 295, 'hours_to_gate' => 5.5],
        'masindi' => ['label' => 'Masindi', 'km_to_gate' => 40, 'hours_to_gate' => 1],
    ],
    'gate_to_paraa_km' => 45,
    'park_driving_km_per_day' => 60,

    // General pattern; individual years differ.
    'months' => [
        1 => ['January', 'Dry', 'Hot and dry; excellent game viewing as animals gather near water.'],
        2 => ['February', 'Dry', 'Hot and dry; one of the best months for game drives.'],
        3 => ['March', 'Wet', 'Rains begin; landscapes turn green.'],
        4 => ['April', 'Wet', 'Wettest period; some tracks can be muddy.'],
        5 => ['May', 'Wet', 'Rainy; fewer visitors and lush scenery.'],
        6 => ['June', 'Dry', 'Dry season begins; very good for all activities.'],
        7 => ['July', 'Dry', 'Dry and popular; book lodges early.'],
        8 => ['August', 'Dry', 'Mostly dry; good wildlife viewing.'],
        9 => ['September', 'Wet', 'Short rains may start; still good boat trips.'],
        10 => ['October', 'Wet', 'Short rains; afternoon showers common.'],
        11 => ['November', 'Wet', 'Short rains continue; excellent birding.'],
        12 => ['December', 'Dry', 'Dry season returns; busy holiday period.'],
    ],

    'notices' => [
        ['Confirm fees before you travel', 'Park and activity fees in this system are planning estimates. Confirm the current UWA tariff before paying.'],
        ['Yellow fever certificate', 'Visitors to Uganda need a valid yellow fever vaccination certificate.'],
        ['Paraa ferry', 'The ferry runs at set times during the day. Check the schedule at the gate and plan game drives around it.'],
        ['Tsetse flies', 'Tsetse flies are common in wooded areas. Wear light-coloured long sleeves and use insect repellent.'],
        ['Money', 'Card payments are not accepted everywhere. Carry some cash or mobile money; Masindi is the last main town with banks.'],
        ['Malaria', 'Murchison Falls is in a malaria area. Take preventive medicine and sleep under a mosquito net.'],
    ],
];

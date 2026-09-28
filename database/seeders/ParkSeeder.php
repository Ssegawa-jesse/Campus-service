<?php

namespace Database\Seeders;

use App\Models\Park;
use App\Services\PlaceholderImageGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Uganda's ten national parks (UWA). Costs are INDICATIVE foreign non-resident USD
 * prices – always verify current tariffs at https://www.ugandawildlife.org before publishing.
 * Images are generated placeholders: replace them in Admin → Parks → Gallery / 360° Scenes.
 */
class ParkSeeder extends Seeder
{
    public function run(PlaceholderImageGenerator $images): void
    {
        foreach ($this->parks() as $i => $data) {
            $scenes = $data['scenes'];
            $activities = $data['activities'];
            $theme = $data['theme'];
            unset($data['scenes'], $data['activities'], $data['theme']);

            $slug = Str::slug($data['name']);
            $dir = 'uploads/seed/'.$slug;

            $cover = "{$dir}/cover.jpg";
            if (! file_exists(public_path($cover))) {
                $images->photo(public_path($cover), $theme, $i + 11);
            }

            $park = Park::updateOrCreate(['slug' => $slug], $data + ['slug' => $slug, 'cover_image' => $cover]);

            $park->activities()->delete();
            $park->activities()->createMany($activities);

            // Gallery: two extra placeholder photos per park
            $park->media()->delete();
            foreach ([1, 2] as $n) {
                $path = "{$dir}/gallery-{$n}.jpg";
                if (! file_exists(public_path($path))) {
                    $images->photo(public_path($path), $scenes[$n]['theme'] ?? $theme, ($i + 1) * 100 + $n);
                }
                $park->media()->create(['type' => 'image', 'path' => $path, 'caption' => $scenes[$n]['title'] ?? $data['name'], 'credit' => 'Placeholder – replace with your photo', 'sort_order' => $n]);
            }

            // 360° scenes, linked to each other in a loop with navigation hotspots
            $park->scenes()->delete();
            $created = [];
            foreach ($scenes as $s => $scene) {
                $path = "{$dir}/pano-{$s}.jpg";
                if (! file_exists(public_path($path))) {
                    $images->panorama(public_path($path), $scene['theme'], ($i + 1) * 10 + $s);
                }
                $created[] = $park->scenes()->create([
                    'title' => $scene['title'],
                    'description' => $scene['description'],
                    'image_path' => $path,
                    'sort_order' => $s,
                ]);
            }

            foreach ($created as $s => $scene) {
                $next = $created[($s + 1) % count($created)];
                $prev = $created[($s - 1 + count($created)) % count($created)];
                $scene->hotspots()->create(['type' => 'scene', 'target_scene_id' => $next->id, 'pitch' => -8, 'yaw' => 30, 'title' => 'Go to: '.$next->title]);
                if ($prev->id !== $next->id) {
                    $scene->hotspots()->create(['type' => 'scene', 'target_scene_id' => $prev->id, 'pitch' => -8, 'yaw' => -150, 'title' => 'Back to: '.$prev->title]);
                }
                foreach ($scenes[$s]['info'] as $k => [$title, $text]) {
                    $scene->hotspots()->create(['type' => 'info', 'pitch' => 4 + $k * 6, 'yaw' => -40 + $k * 110, 'title' => $title, 'info_text' => $text]);
                }
            }
        }
    }

    private function parks(): array
    {
        $dryA = [1, 2, 6, 7, 8, 12];         // June–August & December–February
        $dryB = [1, 2, 6, 7, 8, 9, 12];

        return [
            [
                'name' => 'Bwindi Impenetrable National Park', 'region' => 'South-western', 'type' => 'national_park',
                'tagline' => 'Home to nearly half of the world\'s mountain gorillas',
                'description' => "Bwindi's ancient montane rainforest in Kanungu and Kisoro districts shelters around half of the world's remaining mountain gorillas. Visitors trek through steep, misty valleys in four sectors – Buhoma, Ruhija, Rushaga and Nkuringo – to spend an unforgettable hour with a habituated gorilla family. The forest is also a birding hotspot with many Albertine Rift endemics, and the Batwa community offers cultural experiences that tell the story of the forest's original people. Bwindi was listed as a UNESCO World Heritage Site in 1994.",
                'how_to_get_there' => 'About 8–10 hours by road from Kampala via Mbarara and Kabale/Rukungiri, or scheduled/charter flights from Entebbe to Kihihi or Kisoro airstrips.',
                'best_time' => 'June–August and December–February (drier months)', 'best_months' => $dryA,
                'interests' => ['gorillas', 'primates', 'birding', 'hiking', 'culture'],
                'difficulty_level' => 3, 'min_cost' => 840, 'min_days' => 2, 'area_km2' => 321, 'distance_from_kampala_km' => 530,
                'established' => 1991, 'latitude' => -1.0500, 'longitude' => 29.7000, 'is_unesco' => true, 'theme' => 'forest',
                'activities' => [
                    ['name' => 'Mountain gorilla trekking', 'category' => 'primates', 'fitness_level' => 3, 'duration' => '2–8 hours', 'indicative_cost' => 800, 'description' => 'Track a habituated gorilla family and spend one hour with them.'],
                    ['name' => 'Gorilla habituation experience', 'category' => 'primates', 'fitness_level' => 3, 'duration' => 'Full day (4 hrs with gorillas)', 'indicative_cost' => 1500, 'description' => 'Join researchers habituating a semi-wild family (Rushaga sector).'],
                    ['name' => 'Batwa cultural trail', 'category' => 'culture', 'fitness_level' => 1, 'duration' => '3–4 hours', 'indicative_cost' => 80, 'description' => 'Learn hunting, gathering and forest traditions from the Batwa.'],
                    ['name' => 'Forest birding walk', 'category' => 'birding', 'fitness_level' => 2, 'duration' => 'Half day', 'indicative_cost' => 30, 'description' => 'Look for Albertine Rift endemics such as the African green broadbill.'],
                    ['name' => 'Munyaga River / waterfall nature walk', 'category' => 'nature', 'fitness_level' => 1, 'duration' => '2–3 hours', 'indicative_cost' => 30, 'description' => 'Gentle walk to forest waterfalls near Buhoma.'],
                ],
                'scenes' => [
                    ['title' => 'Buhoma forest trailhead', 'theme' => 'forest', 'description' => 'The starting point for gorilla treks in the Buhoma sector.', 'info' => [['Briefing point', 'Rangers brief trekkers here at about 7:30am before groups set off.'], ['Rules', 'Keep 7 metres from gorillas, no flash photography, and one hour with the family.']]],
                    ['title' => 'Inside the impenetrable forest', 'theme' => 'forest', 'description' => 'Dense vegetation on the slopes where gorilla families feed.', 'info' => [['Gorilla family', 'Habituated families move daily – trackers radio the guides with their location.']]],
                    ['title' => 'Misty valley viewpoint', 'theme' => 'mountain', 'description' => 'Rolling forested hills above Nkuringo.', 'info' => [['Virunga view', 'On clear days you can see the Virunga volcanoes to the south.']]],
                ],
            ],
            [
                'name' => 'Mgahinga Gorilla National Park', 'region' => 'South-western', 'type' => 'national_park',
                'tagline' => 'Where silver meets gold – gorillas and golden monkeys',
                'description' => 'Uganda\'s smallest national park sits on the slopes of three extinct Virunga volcanoes – Muhavura, Gahinga and Sabyinyo – where Uganda, Rwanda and DR Congo meet. It is the only place in Uganda to track both mountain gorillas and the endangered golden monkey. Volcano hikes reward climbers with crater lakes and views across three countries, and the Batwa Trail explores caves once used by the forest\'s first people.',
                'how_to_get_there' => 'About 9–10 hours from Kampala via Kabale to Kisoro town, then 14 km to Ntebeko gate. Flights available to Kisoro airstrip.',
                'best_time' => 'June–August and December–February', 'best_months' => $dryA,
                'interests' => ['gorillas', 'primates', 'hiking', 'culture', 'birding'],
                'difficulty_level' => 3, 'min_cost' => 100, 'min_days' => 2, 'area_km2' => 34, 'distance_from_kampala_km' => 540,
                'established' => 1991, 'latitude' => -1.3667, 'longitude' => 29.6333, 'is_unesco' => false, 'theme' => 'volcano',
                'activities' => [
                    ['name' => 'Mountain gorilla trekking (Nyakagezi family)', 'category' => 'primates', 'fitness_level' => 3, 'duration' => '3–6 hours', 'indicative_cost' => 800, 'description' => 'Track the Nyakagezi gorilla group on the volcano slopes.'],
                    ['name' => 'Golden monkey tracking', 'category' => 'primates', 'fitness_level' => 2, 'duration' => '3–4 hours', 'indicative_cost' => 60, 'description' => 'Follow playful golden monkeys through the bamboo forest.'],
                    ['name' => 'Mount Sabyinyo / Muhavura volcano hike', 'category' => 'hiking', 'fitness_level' => 3, 'duration' => 'Full day', 'indicative_cost' => 80, 'description' => 'Climb to the summit where three countries meet.'],
                    ['name' => 'Batwa Trail', 'category' => 'culture', 'fitness_level' => 1, 'duration' => '4 hours', 'indicative_cost' => 80, 'description' => 'Visit Garama Cave with Batwa guides.'],
                    ['name' => 'Gorge trail birding', 'category' => 'birding', 'fitness_level' => 2, 'duration' => 'Half day', 'indicative_cost' => 30, 'description' => 'Look for the Rwenzori turaco and other montane birds.'],
                ],
                'scenes' => [
                    ['title' => 'Ntebeko gate & volcano view', 'theme' => 'volcano', 'description' => 'The three Virunga volcanoes rise above the park gate.', 'info' => [['Muhavura', 'At 4,127 m, Muhavura is the highest of the three volcanoes.'], ['Three countries', 'Sabyinyo\'s peak is where Uganda, Rwanda and DR Congo meet.']]],
                    ['title' => 'Bamboo forest', 'theme' => 'forest', 'description' => 'Golden monkey habitat on the lower slopes.', 'info' => [['Golden monkeys', 'Endangered primates found only in the Virunga area.']]],
                    ['title' => 'Crater rim', 'theme' => 'mountain', 'description' => 'View from the top of Mount Gahinga.', 'info' => [['Crater swamp', 'Gahinga\'s summit holds a swampy crater about 180 m wide.']]],
                ],
            ],
            [
                'name' => 'Queen Elizabeth National Park', 'region' => 'Western', 'type' => 'national_park',
                'tagline' => 'Tree-climbing lions and the Kazinga Channel',
                'description' => 'Queen Elizabeth is Uganda\'s most visited savannah park, stretching from the Rwenzori foothills to Lake Edward. The Kazinga Channel boat cruise brings visitors close to hippos, elephants, buffaloes and hundreds of water birds, while the Ishasha sector is famous for its tree-climbing lions. Explosion craters, the Kyambura "Valley of Apes" chimpanzees, the Maramagambo Forest and community tourism at Lake Katwe salt pans make this one of the most diverse parks in Africa, with over 600 bird species.',
                'how_to_get_there' => 'About 6–7 hours from Kampala via Mbarara or Fort Portal. Airstrips at Mweya and Kasese.',
                'best_time' => 'June–September and December–February', 'best_months' => $dryB,
                'interests' => ['big_game', 'boat', 'birding', 'chimpanzees', 'primates', 'culture'],
                'difficulty_level' => 1, 'min_cost' => 75, 'min_days' => 2, 'area_km2' => 1978, 'distance_from_kampala_km' => 400,
                'established' => 1952, 'latitude' => -0.1900, 'longitude' => 29.9000, 'is_unesco' => false, 'theme' => 'savannah',
                'activities' => [
                    ['name' => 'Kazinga Channel boat cruise', 'category' => 'boat', 'fitness_level' => 1, 'duration' => '2 hours', 'indicative_cost' => 35, 'description' => 'See hippos, crocodiles, elephants and water birds along the channel.'],
                    ['name' => 'Kasenyi game drive', 'category' => 'game_drive', 'fitness_level' => 1, 'duration' => '3–4 hours', 'indicative_cost' => 30, 'description' => 'Morning drive for lions, Uganda kob and elephants.'],
                    ['name' => 'Ishasha tree-climbing lions', 'category' => 'game_drive', 'fitness_level' => 1, 'duration' => 'Half day', 'indicative_cost' => 30, 'description' => 'Search the fig trees of Ishasha for resting lions.'],
                    ['name' => 'Kyambura Gorge chimpanzee tracking', 'category' => 'primates', 'fitness_level' => 2, 'duration' => '3 hours', 'indicative_cost' => 50, 'description' => 'Descend into the "Valley of Apes".'],
                    ['name' => 'Lake Katwe salt works community tour', 'category' => 'culture', 'fitness_level' => 1, 'duration' => '2 hours', 'indicative_cost' => 20, 'description' => 'See traditional salt mining in a crater lake.'],
                    ['name' => 'Explosion craters drive', 'category' => 'nature', 'fitness_level' => 1, 'duration' => '2–3 hours', 'indicative_cost' => 20, 'description' => 'Scenic drive between volcanic crater lakes.'],
                ],
                'scenes' => [
                    ['title' => 'Kazinga Channel', 'theme' => 'water', 'description' => 'The 32 km channel joining Lake George and Lake Edward.', 'info' => [['Hippos', 'One of the largest hippo concentrations in Africa.'], ['Mweya peninsula', 'Lodges and the launch jetty sit on the peninsula above the channel.']]],
                    ['title' => 'Kasenyi plains', 'theme' => 'savannah', 'description' => 'Open grassland with Uganda kob breeding grounds.', 'info' => [['Uganda kob', 'The national antelope, seen on Uganda\'s coat of arms.']]],
                    ['title' => 'Ishasha sunset', 'theme' => 'sunset', 'description' => 'Fig trees where lions rest in the afternoon.', 'info' => [['Tree-climbing lions', 'Lions climb fig trees to escape heat and insects.']]],
                ],
            ],
            [
                'name' => 'Murchison Falls National Park', 'region' => 'North-western', 'type' => 'national_park',
                'tagline' => 'Where the Nile explodes through a 7-metre gorge',
                'description' => 'Uganda\'s largest and oldest national park is split by the Victoria Nile, which squeezes through a narrow gorge and plunges 43 metres at the Murchison Falls. North of the river, the Buligi grasslands host giraffes, elephants, lions and buffaloes, while launch trips to the base of the falls and the Nile delta offer excellent views of hippos, crocodiles and the rare shoebill stork. Budongo Forest nearby offers chimpanzee tracking.',
                'how_to_get_there' => 'About 5–6 hours from Kampala via Masindi (with a stop at Ziwa Rhino Sanctuary). Airstrips at Pakuba and Bugungu.',
                'best_time' => 'December–February and June–September', 'best_months' => $dryB,
                'interests' => ['big_game', 'boat', 'waterfalls', 'birding', 'chimpanzees', 'hiking'],
                'difficulty_level' => 1, 'min_cost' => 75, 'min_days' => 2, 'area_km2' => 3840, 'distance_from_kampala_km' => 305,
                'established' => 1952, 'latitude' => 2.2500, 'longitude' => 31.8000, 'is_unesco' => false, 'theme' => 'water',
                'activities' => [
                    ['name' => 'Launch trip to the bottom of the falls', 'category' => 'boat', 'fitness_level' => 1, 'duration' => '3 hours', 'indicative_cost' => 35, 'description' => 'Cruise upstream from Paraa to the foot of the falls.'],
                    ['name' => 'Hike to the top of the falls', 'category' => 'hiking', 'fitness_level' => 2, 'duration' => '1–2 hours', 'indicative_cost' => 15, 'description' => 'Walk up from the river to feel the power of the Nile.'],
                    ['name' => 'Buligi game drive', 'category' => 'game_drive', 'fitness_level' => 1, 'duration' => '3–4 hours', 'indicative_cost' => 30, 'description' => 'Rothschild\'s giraffes, elephants and lions on the northern bank.'],
                    ['name' => 'Nile delta shoebill cruise', 'category' => 'birding', 'fitness_level' => 1, 'duration' => '4–5 hours', 'indicative_cost' => 50, 'description' => 'Search the papyrus for the prehistoric-looking shoebill.'],
                    ['name' => 'Budongo chimpanzee tracking', 'category' => 'primates', 'fitness_level' => 2, 'duration' => '3–4 hours', 'indicative_cost' => 130, 'description' => 'Track chimps in the Kaniyo Pabidi forest.'],
                ],
                'scenes' => [
                    ['title' => 'Top of Murchison Falls', 'theme' => 'water', 'description' => 'The Nile forces its way through a 7 m wide gap.', 'info' => [['Power of the Nile', 'Around 300 cubic metres of water a second pass through the gorge.'], ['Uhuru Falls', 'A second, wider fall formed after the 1962 floods.']]],
                    ['title' => 'Paraa & the Victoria Nile', 'theme' => 'water', 'description' => 'The ferry crossing and launch jetty.', 'info' => [['Ferry', 'The Paraa ferry links the southern and northern banks.']]],
                    ['title' => 'Buligi grasslands', 'theme' => 'savannah', 'description' => 'Borassus palms and giraffe country.', 'info' => [['Rothschild\'s giraffe', 'Murchison holds one of the largest populations of this subspecies.']]],
                ],
            ],
            [
                'name' => 'Kidepo Valley National Park', 'region' => 'North-eastern', 'type' => 'national_park',
                'tagline' => 'Africa\'s untouched wilderness in the Karamoja drylands',
                'description' => 'Tucked into the far north-east corner of Uganda on the borders of South Sudan and Kenya, Kidepo Valley is a rugged savannah surrounded by mountains. Its remoteness rewards visitors with uncrowded game viewing: large herds of buffalo, elephants, lions, cheetahs, ostriches and species found nowhere else in Uganda such as the striped hyena and bat-eared fox. Visits to Karamojong and Ik communities add a rich cultural dimension.',
                'how_to_get_there' => 'About 10–12 hours by road from Kampala via Gulu and Kitgum, or a 2-hour scheduled/charter flight to Apoka airstrip.',
                'best_time' => 'September–March (dry season)', 'best_months' => [1, 2, 3, 9, 10, 11, 12],
                'interests' => ['big_game', 'remote', 'culture', 'birding', 'hiking'],
                'difficulty_level' => 1, 'min_cost' => 70, 'min_days' => 3, 'area_km2' => 1442, 'distance_from_kampala_km' => 700,
                'established' => 1962, 'latitude' => 3.8200, 'longitude' => 33.8500, 'is_unesco' => false, 'theme' => 'sunset',
                'activities' => [
                    ['name' => 'Narus Valley game drive', 'category' => 'game_drive', 'fitness_level' => 1, 'duration' => '3–4 hours', 'indicative_cost' => 30, 'description' => 'Year-round water attracts lions, elephants and buffalo.'],
                    ['name' => 'Kidepo Valley & Kanangorok hot springs', 'category' => 'nature', 'fitness_level' => 1, 'duration' => 'Full day', 'indicative_cost' => 30, 'description' => 'Drive to the sand river and hot springs near the border.'],
                    ['name' => 'Karamojong manyatta visit', 'category' => 'culture', 'fitness_level' => 1, 'duration' => '2–3 hours', 'indicative_cost' => 25, 'description' => 'Visit a traditional Karamojong homestead.'],
                    ['name' => 'Morungole mountain hike (Ik community)', 'category' => 'hiking', 'fitness_level' => 3, 'duration' => 'Full day', 'indicative_cost' => 40, 'description' => 'Hike to meet the Ik people in the Morungole range.'],
                    ['name' => 'Savannah birding', 'category' => 'birding', 'fitness_level' => 1, 'duration' => 'Half day', 'indicative_cost' => 30, 'description' => 'Ostriches, Kori bustards and many raptors.'],
                ],
                'scenes' => [
                    ['title' => 'Narus Valley', 'theme' => 'savannah', 'description' => 'The park\'s wildlife heartland.', 'info' => [['Buffalo herds', 'Herds of several hundred buffalo are common.'], ['Apoka', 'Rest camp and park headquarters overlook the valley.']]],
                    ['title' => 'Sunset over the mountains', 'theme' => 'sunset', 'description' => 'The Morungole range at dusk.', 'info' => [['Remote wilderness', 'Often called one of Africa\'s finest wilderness areas.']]],
                    ['title' => 'Kanangorok hot springs', 'theme' => 'savannah', 'description' => 'Near the South Sudan border.', 'info' => [['Hot springs', 'Water emerges at over 50°C.']]],
                ],
            ],
            [
                'name' => 'Lake Mburo National Park', 'region' => 'Western', 'type' => 'national_park',
                'tagline' => 'Zebras, walking safaris and horseback rides – close to Kampala',
                'description' => 'The closest savannah park to Kampala, Lake Mburo is a compact mosaic of acacia woodland, rolling hills and five lakes. It is the only park in western Uganda with zebras and impalas, and one of the few where you can explore on foot, by bicycle or on horseback. Boat trips on Lake Mburo reveal hippos, crocodiles and the African finfoot, making it ideal for a short break or a stop on the way to the south-west.',
                'how_to_get_there' => 'About 3.5–4 hours from Kampala on the Masaka–Mbarara road, turning at Sanga or Nshaara gates.',
                'best_time' => 'June–September and December–February', 'best_months' => $dryB,
                'interests' => ['walking_safari', 'boat', 'big_game', 'birding'],
                'difficulty_level' => 1, 'min_cost' => 70, 'min_days' => 1, 'area_km2' => 370, 'distance_from_kampala_km' => 230,
                'established' => 1983, 'latitude' => -0.6000, 'longitude' => 30.9500, 'is_unesco' => false, 'theme' => 'savannah',
                'activities' => [
                    ['name' => 'Guided walking safari', 'category' => 'nature', 'fitness_level' => 1, 'duration' => '2–3 hours', 'indicative_cost' => 30, 'description' => 'Walk among zebras, impalas and elands with an armed ranger.'],
                    ['name' => 'Horseback safari', 'category' => 'nature', 'fitness_level' => 2, 'duration' => '1–4 hours', 'indicative_cost' => 55, 'description' => 'Ride alongside zebras – the only horseback safari in Uganda.'],
                    ['name' => 'Lake Mburo boat trip', 'category' => 'boat', 'fitness_level' => 1, 'duration' => '2 hours', 'indicative_cost' => 30, 'description' => 'Hippos, crocodiles and the elusive African finfoot.'],
                    ['name' => 'Night game drive', 'category' => 'game_drive', 'fitness_level' => 1, 'duration' => '2 hours', 'indicative_cost' => 40, 'description' => 'Spot leopards, hyenas and bush babies after dark.'],
                    ['name' => 'Mountain biking', 'category' => 'nature', 'fitness_level' => 2, 'duration' => '2–3 hours', 'indicative_cost' => 25, 'description' => 'Cycle the trails of the Rwonyo area.'],
                ],
                'scenes' => [
                    ['title' => 'Acacia savannah', 'theme' => 'savannah', 'description' => 'Zebras graze between the acacias.', 'info' => [['Zebras', 'Lake Mburo is the best place in Uganda to see Burchell\'s zebra.'], ['Walking safari', 'Walks start early from Rwonyo.']]],
                    ['title' => 'Lake Mburo shore', 'theme' => 'water', 'description' => 'The jetty for boat trips.', 'info' => [['Five lakes', 'The park holds 5 of the 14 lakes of the Mburo wetland system.']]],
                    ['title' => 'Kigambira loop at dusk', 'theme' => 'sunset', 'description' => 'Rolling hills at sunset.', 'info' => [['Leopards', 'Night drives often reveal leopards.']]],
                ],
            ],
            [
                'name' => 'Rwenzori Mountains National Park', 'region' => 'Western', 'type' => 'national_park',
                'tagline' => 'The legendary Mountains of the Moon',
                'description' => 'The Rwenzori Mountains form a snow-capped range on the Uganda–DR Congo border, rising to 5,109 m at Margherita Peak on Mount Stanley – the third-highest point in Africa. Trekkers pass through otherworldly vegetation zones of giant lobelias, groundsels and moss-draped forests, as well as glaciers, waterfalls and alpine lakes. Shorter day hikes and Bakonzo cultural trails make the park accessible to non-climbers. It became a UNESCO World Heritage Site in 1994.',
                'how_to_get_there' => 'About 6–7 hours from Kampala to Kasese via Fort Portal, then to Nyakalengija or Kilembe trailheads. Flights to Kasese airstrip.',
                'best_time' => 'June–August and December–February', 'best_months' => $dryA,
                'interests' => ['hiking', 'remote', 'culture', 'birding', 'waterfalls'],
                'difficulty_level' => 3, 'min_cost' => 120, 'min_days' => 2, 'area_km2' => 996, 'distance_from_kampala_km' => 375,
                'established' => 1991, 'latitude' => 0.3500, 'longitude' => 29.9500, 'is_unesco' => true, 'theme' => 'mountain',
                'activities' => [
                    ['name' => 'Margherita Peak summit trek', 'category' => 'hiking', 'fitness_level' => 3, 'duration' => '7–9 days', 'indicative_cost' => 1200, 'description' => 'Technical climb to Africa\'s third-highest peak (packages via Rwenzori Mountaineering Services or Rwenzori Trekking Services).'],
                    ['name' => 'Day hike to Lake Mahoma', 'category' => 'hiking', 'fitness_level' => 2, 'duration' => '1–2 days', 'indicative_cost' => 120, 'description' => 'Forest hike to a beautiful montane lake.'],
                    ['name' => 'Bakonzo cultural trail', 'category' => 'culture', 'fitness_level' => 1, 'duration' => 'Half day', 'indicative_cost' => 30, 'description' => 'Visit villages at the foot of the mountains.'],
                    ['name' => 'Montane forest birding', 'category' => 'birding', 'fitness_level' => 2, 'duration' => 'Half day', 'indicative_cost' => 30, 'description' => 'Rwenzori turaco and many Albertine Rift endemics.'],
                ],
                'scenes' => [
                    ['title' => 'Glacier view', 'theme' => 'mountain', 'description' => 'Snow and ice on the high peaks.', 'info' => [['Margherita Peak', '5,109 m – the third-highest summit in Africa.'], ['Retreating glaciers', 'The glaciers have shrunk significantly over the last century.']]],
                    ['title' => 'Giant lobelia valley', 'theme' => 'mountain', 'description' => 'Afro-alpine vegetation zone.', 'info' => [['Giant plants', 'Lobelias and groundsels grow several metres tall here.']]],
                    ['title' => 'Mubuku river forest', 'theme' => 'forest', 'description' => 'The start of the central circuit trail.', 'info' => [['Nyakalengija', 'Main trailhead for the central circuit.']]],
                ],
            ],
            [
                'name' => 'Kibale National Park', 'region' => 'Western', 'type' => 'national_park',
                'tagline' => 'The primate capital of the world',
                'description' => 'Kibale\'s lush tropical forest near Fort Portal holds one of the highest densities of primates on earth, including about 1,500 chimpanzees and 12 other primate species such as the red colobus and L\'Hoest\'s monkey. Chimpanzee tracking from Kanyanchu has very high success rates, and the full-day habituation experience lets visitors spend time with a community from dawn. Nearby Bigodi Wetland Sanctuary and the Fort Portal crater lakes complete the experience.',
                'how_to_get_there' => 'About 5–6 hours from Kampala via Mubende and Fort Portal. Kanyanchu visitor centre is 35 km south-east of Fort Portal.',
                'best_time' => 'June–September and December–February', 'best_months' => $dryB,
                'interests' => ['chimpanzees', 'primates', 'birding', 'culture', 'hiking'],
                'difficulty_level' => 2, 'min_cost' => 290, 'min_days' => 1, 'area_km2' => 795, 'distance_from_kampala_km' => 320,
                'established' => 1993, 'latitude' => 0.4400, 'longitude' => 30.4000, 'is_unesco' => false, 'theme' => 'forest',
                'activities' => [
                    ['name' => 'Chimpanzee tracking', 'category' => 'primates', 'fitness_level' => 2, 'duration' => '3–4 hours', 'indicative_cost' => 250, 'description' => 'One hour with a habituated chimp community from Kanyanchu.'],
                    ['name' => 'Chimpanzee habituation experience', 'category' => 'primates', 'fitness_level' => 3, 'duration' => 'Full day', 'indicative_cost' => 300, 'description' => 'Spend the whole day with researchers and a chimp community.'],
                    ['name' => 'Bigodi Wetland Sanctuary walk', 'category' => 'birding', 'fitness_level' => 1, 'duration' => '3 hours', 'indicative_cost' => 20, 'description' => 'Community-run swamp walk – great blue turaco and primates.'],
                    ['name' => 'Night forest walk', 'category' => 'nature', 'fitness_level' => 1, 'duration' => '2 hours', 'indicative_cost' => 40, 'description' => 'Find pottos, bush babies and nocturnal insects.'],
                    ['name' => 'Crater lakes hike', 'category' => 'hiking', 'fitness_level' => 2, 'duration' => 'Half day', 'indicative_cost' => 15, 'description' => 'Explore the Ndali–Kasenda crater lakes near the park.'],
                ],
                'scenes' => [
                    ['title' => 'Kanyanchu forest', 'theme' => 'forest', 'description' => 'Tall forest where chimp tracking begins.', 'info' => [['13 primate species', 'Including chimpanzees, red colobus and grey-cheeked mangabey.'], ['High success', 'Chimp sightings exceed 90% on most days.']]],
                    ['title' => 'Bigodi wetland', 'theme' => 'water', 'description' => 'Community-managed papyrus swamp.', 'info' => [['Community tourism', 'Revenue supports local schools and health.']]],
                    ['title' => 'Crater lake viewpoint', 'theme' => 'mountain', 'description' => 'Crater lakes near Fort Portal.', 'info' => [['Top of the World', 'A viewpoint overlooking three crater lakes.']]],
                ],
            ],
            [
                'name' => 'Semuliki National Park', 'region' => 'Western', 'type' => 'national_park',
                'tagline' => 'Boiling hot springs and Central African forest',
                'description' => 'Semuliki protects the easternmost extension of the great Ituri Forest of the Congo Basin, making it one of Africa\'s most biodiverse forests and a paradise for birders, with over 440 species including many Central African specials. The famous Sempaya hot springs – the "male" Bitente and "female" Nyasimbi – bubble up at over 100°C. The park is also home to Batwa communities and the Semliki River that flows into Lake Albert.',
                'how_to_get_there' => 'About 6–7 hours from Kampala via Fort Portal, then 52 km over the Rwenzori foothills to Sempaya.',
                'best_time' => 'June–September and December–February', 'best_months' => $dryB,
                'interests' => ['hot_springs', 'birding', 'primates', 'culture', 'remote'],
                'difficulty_level' => 1, 'min_cost' => 65, 'min_days' => 1, 'area_km2' => 220, 'distance_from_kampala_km' => 380,
                'established' => 1993, 'latitude' => 0.8300, 'longitude' => 30.1500, 'is_unesco' => false, 'theme' => 'forest',
                'activities' => [
                    ['name' => 'Sempaya hot springs walk', 'category' => 'nature', 'fitness_level' => 1, 'duration' => '1–2 hours', 'indicative_cost' => 30, 'description' => 'Boil an egg in the female hot spring.'],
                    ['name' => 'Central African forest birding', 'category' => 'birding', 'fitness_level' => 2, 'duration' => 'Full day', 'indicative_cost' => 30, 'description' => 'Seek hornbills, Nkulengu rail and other Congo specials.'],
                    ['name' => 'Kirumia river trail', 'category' => 'hiking', 'fitness_level' => 2, 'duration' => '8 hours', 'indicative_cost' => 30, 'description' => 'Long forest hike to the Semliki River.'],
                    ['name' => 'Batwa community visit', 'category' => 'culture', 'fitness_level' => 1, 'duration' => '2 hours', 'indicative_cost' => 20, 'description' => 'Meet the Batwa of Ntandi.'],
                ],
                'scenes' => [
                    ['title' => 'Sempaya female hot spring', 'theme' => 'water', 'description' => 'Nyasimbi spring – a boiling geyser.', 'info' => [['Over 100°C', 'Water shoots up to 2 m into the air.'], ['Folklore', 'The Bamaga clan see the springs as sacred.']]],
                    ['title' => 'Ituri forest edge', 'theme' => 'forest', 'description' => 'Lowland Congo-type forest.', 'info' => [['Birding hotspot', 'Over 440 bird species recorded.']]],
                    ['title' => 'Semliki valley', 'theme' => 'savannah', 'description' => 'The Semliki valley towards Lake Albert.', 'info' => [['Semliki River', 'Flows north into Lake Albert along the DR Congo border.']]],
                ],
            ],
            [
                'name' => 'Mount Elgon National Park', 'region' => 'Eastern', 'type' => 'national_park',
                'tagline' => 'One of the world\'s largest volcanic calderas',
                'description' => 'Mount Elgon, an extinct volcano on the Uganda–Kenya border, has one of the largest calderas in the world. Trails from Budadiri, Kapkwai and Kapchorwa climb through bamboo forest and heather to Wagagai Peak (4,321 m) without technical skills. The slopes are dotted with caves, hot springs inside the caldera and the stunning Sipi Falls just outside the park, while the Bagisu and Sabiny cultures add depth to the visit.',
                'how_to_get_there' => 'About 4–5 hours from Kampala via Jinja and Mbale to Budadiri, Kapkwai or Sipi.',
                'best_time' => 'June–August and December–March', 'best_months' => [1, 2, 3, 6, 7, 8, 12],
                'interests' => ['hiking', 'waterfalls', 'culture', 'birding', 'remote'],
                'difficulty_level' => 3, 'min_cost' => 60, 'min_days' => 1, 'area_km2' => 1121, 'distance_from_kampala_km' => 235,
                'established' => 1992, 'latitude' => 1.1333, 'longitude' => 34.5500, 'is_unesco' => false, 'theme' => 'mountain',
                'activities' => [
                    ['name' => 'Wagagai Peak summit hike', 'category' => 'hiking', 'fitness_level' => 3, 'duration' => '4–5 days', 'indicative_cost' => 300, 'description' => 'Non-technical trek to the caldera rim and summit.'],
                    ['name' => 'Sipi Falls hike', 'category' => 'hiking', 'fitness_level' => 2, 'duration' => 'Half day', 'indicative_cost' => 25, 'description' => 'Visit the three tiers of Sipi Falls.'],
                    ['name' => 'Kapkwai cave exploration', 'category' => 'nature', 'fitness_level' => 1, 'duration' => '2–3 hours', 'indicative_cost' => 30, 'description' => 'Walk to caves once used by elephants for salt.'],
                    ['name' => 'Bugisu coffee & culture tour', 'category' => 'culture', 'fitness_level' => 1, 'duration' => '3 hours', 'indicative_cost' => 20, 'description' => 'Follow Arabica coffee from farm to cup.'],
                    ['name' => 'Montane birding', 'category' => 'birding', 'fitness_level' => 2, 'duration' => 'Half day', 'indicative_cost' => 30, 'description' => 'Look for Jackson\'s francolin and the endemic Elgon francolin.'],
                ],
                'scenes' => [
                    ['title' => 'Sipi Falls', 'theme' => 'water', 'description' => 'The main 100 m drop of Sipi Falls.', 'info' => [['Three waterfalls', 'Sipi has three tiers along the Sipi River.'], ['Coffee country', 'The slopes produce the famous Bugisu Arabica.']]],
                    ['title' => 'Elgon caldera', 'theme' => 'mountain', 'description' => 'Inside the massive volcanic caldera.', 'info' => [['Caldera', 'Around 40 km across – one of the largest in the world.']]],
                    ['title' => 'Bamboo zone', 'theme' => 'forest', 'description' => 'Bamboo forest on the Sasa trail.', 'info' => [['Sasa trail', 'Shortest but steepest route to the summit.']]],
                ],
            ],
        ];
    }
}

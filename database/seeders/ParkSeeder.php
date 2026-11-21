<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Park;
use App\Models\Place;
use App\Services\PlaceholderImageGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * MURCHISON FALLS NATIONAL PARK ONLY (research scope).
 * Seeds the park, the Smart Navigation places (gates, Paraa Jetty, Baker's Trail, Pakuba Airfield,
 * attractions), the seven research activities with UWA tiered prices, a placeholder 360° scene per
 * activity (replace with HP5 360 VR captures in Admin → 360° Studio) and a gallery.
 * Coordinates are approximate (for orientation, not for navigation). Prices are indicative UWA
 * planning figures — confirm the current tariff with the Uganda Wildlife Authority.
 */
class ParkSeeder extends Seeder
{
    public function run(PlaceholderImageGenerator $images): void
    {
        // Research scope: remove any other park left over from earlier versions.
        Park::where('slug', '!=', config('murchison.park_slug'))->delete();

        $dir = 'uploads/seed/murchison-falls-national-park';
        $park = Park::updateOrCreate(['slug' => config('murchison.park_slug')], [
            'name' => 'Murchison Falls National Park', 'type' => 'national_park', 'region' => 'North-western Uganda',
            'tagline' => 'Where the Victoria Nile explodes through a 7-metre gorge',
            'description' => "Uganda's largest and oldest national park (≈3,893 km², gazetted 1952) is split by the Victoria Nile, which squeezes through a 7-metre gap and plunges 43 metres at Murchison Falls before flowing west into Lake Albert. North of the river the Buligi grasslands hold elephants, Rothschild's giraffes, lions, buffaloes, Uganda kobs and Jackson's hartebeest. Boat cruises from Paraa reach the base of the falls and the papyrus delta, home of the rare shoebill, while Budongo Forest in the south of the conservation area shelters habituated chimpanzees. The park is managed by the Uganda Wildlife Authority (UWA).",
            'how_to_get_there' => 'About 305 km (5–6 hours) from Kampala via Luweero and Masindi to Kichumbanyobo Gate. Other gates: Bugungu (west), Tangi and Chobe (east, near Karuma), Wankwar (north). Scheduled and charter flights land at Pakuba Airfield.',
            'best_time' => 'December–February and June–September (dry seasons)', 'best_months' => [1, 2, 6, 7, 8, 9, 12],
            'interests' => ['big_game', 'boat', 'waterfalls', 'birding', 'chimpanzees', 'hiking'],
            'difficulty_level' => 1, 'min_cost' => 45, 'min_days' => 2, 'area_km2' => 3893, 'distance_from_kampala_km' => 305,
            'established' => 1952, 'latitude' => 2.2100, 'longitude' => 31.7500, 'is_unesco' => false, 'is_published' => true,
            'cover_image' => "{$dir}/cover.jpg",
        ]);

        // ---- Smart Navigation places --------------------------------------------------
        $places = [];
        foreach ($this->places() as $p) {
            $places[$p['slug']] = Place::updateOrCreate(['slug' => $p['slug']], $p + ['is_active' => true]);
        }

        // ---- Activities with UWA tiered prices -------------------------------------------
        Activity::where('park_id', $park->id)->where(fn ($q) => $q->whereNull('slug')->orWhereNotIn('slug', array_column($this->activities(), 'slug')))->delete();
        $acts = [];
        foreach ($this->activities() as $a) {
            $placeSlug = $a['place'];
            unset($a['place']);
            $acts[$a['slug']] = Activity::updateOrCreate(['slug' => $a['slug']], $a + [
                'park_id' => $park->id, 'place_id' => $places[$placeSlug]->id, 'is_active' => true,
                'indicative_cost' => $a['price_fnr_usd'], 'duration' => rtrim(rtrim(number_format($a['hours'], 1), '0'), '.').' hours',
            ]);
        }

        // ---- Gallery ---------------------------------------------------------------------
        $park->media()->delete();
        $park->media()->create(['type' => 'image', 'path' => "{$dir}/cover.jpg", 'caption' => 'The Victoria Nile at Murchison Falls', 'credit' => 'Placeholder – replace with field photo', 'sort_order' => 1]);
        $park->media()->create(['type' => 'image', 'path' => "{$dir}/gallery-1.jpg", 'caption' => 'North Bank savannah', 'credit' => 'Placeholder – replace with field photo', 'sort_order' => 2, 'activity_id' => $acts['north-bank-game-drive']->id]);
        $park->media()->create(['type' => 'image', 'path' => "{$dir}/gallery-2.jpg", 'caption' => 'Riverine scenery near Paraa', 'credit' => 'Placeholder – replace with field photo', 'sort_order' => 3, 'activity_id' => $acts['victoria-nile-boat-cruise']->id]);

        // ---- 360° scenes: one per activity (placeholders until HP5 captures are uploaded) --
        $park->scenes()->delete();
        $themes = ['victoria-nile-boat-cruise' => ['pano-1.jpg', 'water'], 'north-bank-game-drive' => ['pano-2.jpg', 'savannah'], 'hike-to-the-top-of-the-falls' => ['pano-0.jpg', 'water'],
            'budongo-chimpanzee-trekking' => ['pano-chimps.jpg', 'forest'], 'albert-delta-shoebill-birding' => ['pano-delta.jpg', 'water'],
            'sport-fishing' => ['pano-fishing.jpg', 'water'], 'night-game-drive' => ['pano-night.jpg', 'sunset']];
        $scenes = [];
        $i = 0;
        foreach ($this->sceneInfo() as $slug => [$title, $desc, $info]) {
            [$file, $theme] = $themes[$slug];
            $path = "{$dir}/{$file}";
            if (! file_exists(public_path($path)) && function_exists('imagecreatetruecolor')) {
                $images->panorama(public_path($path), $theme, 70 + $i);
            }
            $scene = $park->scenes()->create([
                'activity_id' => $acts[$slug]->id, 'title' => $title, 'description' => $desc,
                'image_path' => file_exists(public_path($path)) ? $path : 'images/placeholder-pano.jpg',
                'camera' => 'HP5 360 VR (placeholder – replace with field capture)', 'sort_order' => $i++, 'is_published' => true,
            ]);
            foreach ($info as $k => [$t, $text]) {
                $scene->hotspots()->create(['type' => 'info', 'pitch' => -4 + $k * 8, 'yaw' => -60 + $k * 95, 'title' => $t, 'info_text' => $text, 'activity_id' => $k === 0 ? $acts[$slug]->id : null]);
            }
            $scenes[] = $scene;
        }
        foreach ($scenes as $s => $scene) {
            $next = $scenes[($s + 1) % count($scenes)];
            $scene->hotspots()->create(['type' => 'scene', 'target_scene_id' => $next->id, 'pitch' => -10, 'yaw' => 150, 'title' => 'Next: '.$next->title]);
        }
    }

    private function places(): array
    {
        return [
            ['slug' => 'kichumbanyobo-gate', 'name' => 'Kichumbanyobo Gate', 'type' => 'gate', 'zone' => 'south', 'latitude' => 1.9686, 'longitude' => 31.7860, 'opening_hours' => '07:00–19:00', 'description' => 'Main southern entrance for visitors from Kampala via Masindi. Pay UWA park entry here.'],
            ['slug' => 'bugungu-gate', 'name' => 'Bugungu Gate', 'type' => 'gate', 'zone' => 'south', 'latitude' => 2.0060, 'longitude' => 31.5180, 'opening_hours' => '07:00–19:00', 'description' => 'Western entrance on the Butiaba/Hoima side, close to the Albert escarpment.'],
            ['slug' => 'tangi-gate', 'name' => 'Tangi Gate', 'type' => 'gate', 'zone' => 'north', 'latitude' => 2.2690, 'longitude' => 32.0360, 'opening_hours' => '07:00–19:00', 'description' => 'Eastern entrance on the North Bank near Karuma, from the Kampala–Gulu highway.'],
            ['slug' => 'chobe-gate', 'name' => 'Chobe Gate', 'type' => 'gate', 'zone' => 'north', 'latitude' => 2.2400, 'longitude' => 32.1500, 'opening_hours' => '07:00–19:00', 'description' => 'North-eastern entrance near Chobe on the Victoria Nile rapids.'],
            ['slug' => 'wankwar-gate', 'name' => 'Wankwar Gate', 'type' => 'gate', 'zone' => 'north', 'latitude' => 2.5530, 'longitude' => 31.7680, 'opening_hours' => '07:00–19:00', 'description' => 'Northern entrance from Gulu and Purongo.'],
            ['slug' => 'paraa-jetty', 'name' => 'Paraa Jetty & Ferry', 'type' => 'jetty', 'zone' => 'river', 'latitude' => 2.2843, 'longitude' => 31.5655, 'opening_hours' => 'Ferry 07:00–19:00 (see Travel Updates)', 'description' => 'Heart of the park on the Victoria Nile: launch cruises to the falls and the delta depart here and the ferry carries vehicles to the North Bank.', 'tip' => 'Arrive early for the first ferry if you plan a dawn game drive.'],
            ['slug' => 'bakers-trail', 'name' => "Baker's Trail (falls hiking trail)", 'type' => 'trail', 'zone' => 'south', 'latitude' => 2.2772, 'longitude' => 31.6815, 'description' => 'Ranger-guided trail climbing from the launch drop-off at the base of the falls to the Top of the Falls. Named after explorer Sir Samuel Baker, who named the falls in 1864.', 'tip' => 'The rocks are wet from the spray — wear shoes with grip.'],
            ['slug' => 'pakuba-airfield', 'name' => 'Pakuba Airfield', 'type' => 'airfield', 'zone' => 'north', 'latitude' => 2.3260, 'longitude' => 31.5000, 'description' => 'Airstrip on the North Bank with scheduled and charter flights from Entebbe and Kajjansi.'],
            ['slug' => 'top-of-the-falls', 'name' => 'Top of the Falls', 'type' => 'attraction', 'zone' => 'south', 'latitude' => 2.2786, 'longitude' => 31.6857, 'description' => 'Viewpoint where the whole Nile squeezes through a 7 m gorge and drops 43 m. Short trails lead to several viewpoints.', 'tip' => 'Late afternoon light is best for photographs.'],
            ['slug' => 'base-of-the-falls', 'name' => 'Base of the Falls', 'type' => 'attraction', 'zone' => 'river', 'latitude' => 2.2795, 'longitude' => 31.6760, 'description' => 'Turning point of the launch cruise, with hippos, crocodiles and spray from the falls.'],
            ['slug' => 'buligi-game-tracks', 'name' => 'Buligi Game Tracks (North Bank)', 'type' => 'attraction', 'zone' => 'north', 'latitude' => 2.2450, 'longitude' => 31.4400, 'description' => "Open savannah between the Victoria and Albert Nile with the park's best game viewing.", 'tip' => 'Go at dawn, when predators are most active, and take a UWA ranger guide.'],
            ['slug' => 'albert-delta', 'name' => 'Albert Nile Delta', 'type' => 'attraction', 'zone' => 'river', 'latitude' => 2.2800, 'longitude' => 31.4100, 'description' => 'Papyrus swamps where the Victoria Nile enters Lake Albert — one of the best shoebill sites in Africa.', 'tip' => 'Early launches give the best chance of seeing the shoebill.'],
            ['slug' => 'budongo-kaniyo-pabidi', 'name' => 'Budongo Forest (Kaniyo Pabidi)', 'type' => 'forest', 'zone' => 'south', 'latitude' => 1.9200, 'longitude' => 31.7200, 'description' => 'Mahogany forest in the south of the conservation area with habituated chimpanzees.', 'tip' => 'Wear long trousers and boots; trails are muddy after rain.'],
            ['slug' => 'paraa-uwa-bandas', 'name' => 'Paraa UWA Bandas & Campsite', 'type' => 'lodge', 'zone' => 'river', 'latitude' => 2.2820, 'longitude' => 31.5620, 'description' => 'Budget UWA accommodation near Paraa.'],
            ['slug' => 'masindi-town', 'name' => 'Masindi Town', 'type' => 'town', 'zone' => 'route', 'latitude' => 1.6744, 'longitude' => 31.7150, 'description' => 'Last major town before Kichumbanyobo Gate: fuel, banks, mobile money and supermarkets.'],
        ];
    }

    /** The seven research activities with UWA three-tier prices. */
    private function activities(): array
    {
        return [
            ['slug' => 'victoria-nile-boat-cruise', 'name' => 'Victoria Nile Boat Cruise to the Base of the Falls', 'category' => 'boat', 'place' => 'paraa-jetty', 'fitness_level' => 1, 'hours' => 3,
                'price_fnr_usd' => 35, 'price_fr_usd' => 30, 'price_eac_ugx' => 30000, 'slots' => ['afternoon', 'morning'], 'interests' => ['boat', 'waterfalls', 'wildlife', 'birds', 'photography'], 'priority' => 10,
                'description' => 'Three-hour launch from Paraa Jetty upstream to the foot of the falls, passing hippos, crocodiles, elephants and water birds.'],
            ['slug' => 'north-bank-game-drive', 'name' => 'North Bank Game Drive (Buligi)', 'category' => 'game_drive', 'place' => 'buligi-game-tracks', 'fitness_level' => 1, 'hours' => 4,
                'price_fnr_usd' => 0, 'price_fr_usd' => 0, 'price_eac_ugx' => 0, 'group_fee_usd' => 20, 'group_fee_ugx' => 20000, 'slots' => ['dawn', 'afternoon'], 'interests' => ['wildlife', 'photography'], 'priority' => 10,
                'wet_season_note' => 'Some Buligi tracks get muddy in the rains; a 4x4 is recommended.',
                'description' => 'Dawn drive on the Buligi tracks with a UWA ranger guide (fee per vehicle) to find lions, elephants, Rothschild\'s giraffes and antelopes.'],
            ['slug' => 'hike-to-the-top-of-the-falls', 'name' => 'Hike to the Top of the Falls', 'category' => 'hiking', 'place' => 'bakers-trail', 'fitness_level' => 2, 'hours' => 2,
                'price_fnr_usd' => 20, 'price_fr_usd' => 15, 'price_eac_ugx' => 15000, 'slots' => ['afternoon', 'evening', 'morning'], 'interests' => ['hiking', 'waterfalls', 'photography'], 'priority' => 9,
                'wet_season_note' => 'The trail is slippery after rain.',
                'description' => 'Ranger-guided climb along the gorge to the Top of the Falls, where the whole Nile thunders through a 7-metre gap.'],
            ['slug' => 'budongo-chimpanzee-trekking', 'name' => 'Budongo Forest Chimpanzee Trekking', 'category' => 'primates', 'place' => 'budongo-kaniyo-pabidi', 'fitness_level' => 2, 'hours' => 4, 'min_age' => 12,
                'price_fnr_usd' => 130, 'price_fr_usd' => 100, 'price_eac_ugx' => 100000, 'slots' => ['dawn', 'morning'], 'interests' => ['chimps', 'hiking', 'photography'], 'requires' => ['chimps'], 'priority' => 7,
                'wet_season_note' => 'Forest trails are muddy in the rainy season; wear boots.',
                'description' => 'Morning walk through Kaniyo Pabidi in Budongo Forest with a guide to find a habituated chimpanzee community (minimum age 12).'],
            ['slug' => 'albert-delta-shoebill-birding', 'name' => 'Albert Delta Shoebill Birding Cruise', 'category' => 'birding', 'place' => 'albert-delta', 'fitness_level' => 1, 'hours' => 4,
                'price_fnr_usd' => 50, 'price_fr_usd' => 40, 'price_eac_ugx' => 40000, 'slots' => ['dawn', 'morning'], 'interests' => ['birds', 'boat', 'photography'], 'priority' => 6,
                'description' => 'Boat cruise downstream from Paraa to the papyrus delta at Lake Albert in search of the shoebill and other water birds.'],
            ['slug' => 'sport-fishing', 'name' => 'Sport Fishing below the Falls', 'category' => 'fishing', 'place' => 'base-of-the-falls', 'fitness_level' => 2, 'hours' => 4,
                'price_fnr_usd' => 50, 'price_fr_usd' => 40, 'price_eac_ugx' => 50000, 'slots' => ['morning', 'afternoon'], 'interests' => ['fishing'], 'requires' => ['fishing'], 'priority' => 3,
                'description' => 'Catch-and-release fishing for Nile perch and tigerfish with a licensed guide (UWA sport-fishing permit per day).'],
            ['slug' => 'night-game-drive', 'name' => 'Night Game Drive', 'category' => 'game_drive', 'place' => 'buligi-game-tracks', 'fitness_level' => 1, 'hours' => 2,
                'price_fnr_usd' => 30, 'price_fr_usd' => 25, 'price_eac_ugx' => 30000, 'slots' => ['night'], 'interests' => ['night', 'wildlife', 'photography'], 'priority' => 4,
                'description' => 'Ranger-led spotlight drive on the North Bank after dark for leopards, hyenas, bush babies and nightjars.'],
        ];
    }

    private function sceneInfo(): array
    {
        return [
            'victoria-nile-boat-cruise' => ['Victoria Nile launch at Paraa', 'Launch cruise to the base of the falls.', [['Launch cruise', 'Three-hour cruise to the base of the falls — hippos, crocodiles and elephants along the banks.'], ['Paraa Jetty', 'Cruises leave at about 09:00 and 14:00; check Travel Updates for today\'s schedule.']]],
            'north-bank-game-drive' => ['Buligi grasslands (North Bank)', 'Borassus palms and giraffe country.', [['Game drive', 'Dawn drives give the best chance of lions; hire a UWA ranger guide at Paraa.'], ['Rothschild\'s giraffe', 'Murchison holds one of the largest populations of this subspecies.']]],
            'hike-to-the-top-of-the-falls' => ['Top of Murchison Falls', 'The Nile forces its way through a 7 m wide gap.', [['Hike to the top', 'Climb Baker\'s Trail from the launch drop-off to the top of the falls with a ranger.'], ['Power of the Nile', 'Around 300 m³ of water a second pass through the gorge. Uhuru Falls formed beside it after the 1962 floods.']]],
            'budongo-chimpanzee-trekking' => ['Budongo Forest – Kaniyo Pabidi', 'Mahogany forest with habituated chimpanzees.', [['Chimpanzee trekking', 'Morning treks with a guide; one hour with the chimps. Minimum age 12.'], ['Forest rules', 'Keep 8 m away, no eating near the chimps, do not trek if you have a cold.']]],
            'albert-delta-shoebill-birding' => ['Albert Nile Delta papyrus', 'Where the Nile meets Lake Albert.', [['Shoebill cruise', 'Four-hour early cruise downstream from Paraa in search of the shoebill.'], ['Birding', 'Over 450 bird species have been recorded in the park.']]],
            'sport-fishing' => ['Below the Falls – fishing spot', 'Nile perch water below the cascades.', [['Sport fishing', 'Catch-and-release with a UWA permit and licensed guide.']]],
            'night-game-drive' => ['North Bank at dusk', 'Start point of ranger-led night drives.', [['Night game drive', 'Spotlight drive for leopards, hyenas and bush babies with a UWA ranger.']]],
        ];
    }
}

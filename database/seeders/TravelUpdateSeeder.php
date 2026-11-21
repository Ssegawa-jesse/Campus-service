<?php

namespace Database\Seeders;

use App\Models\TravelUpdate;
use Illuminate\Database\Seeder;

/** Starter operational alerts. The researcher/admin edits these live in Admin → Travel Updates. */
class TravelUpdateSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['ferry', 'Paraa ferry crossing schedule', 'The Paraa ferry crosses the Victoria Nile on the hour from 07:00 to 19:00 (last crossing 19:00). Extra crossings may run at 06:30 for dawn game drives — confirm at the Paraa UWA office. Fee ≈UGX 25,000 per vehicle per crossing.', 'info', 'Paraa Jetty', true],
            ['road', 'Masindi – Kichumbanyobo road', 'The Masindi–Kichumbanyobo road is murram for the last stretch. Drive slowly after rain; allow about 1 hour from Masindi town to the gate.', 'info', 'Southern access', false],
            ['gate', 'Gate opening hours', 'All park gates (Kichumbanyobo, Bugungu, Tangi, Chobe, Wankwar) open 07:00–19:00. Pay park entry by card or mobile money where available; carry some cash as backup.', 'info', 'All gates', false],
            ['weather', 'Seasonal weather advisory', 'Short rains can bring afternoon showers between September and November. Northern Buligi tracks may become muddy: a 4x4 is recommended and launch cruises may be delayed during storms.', 'warning', 'North Bank', false],
            ['conservation', 'UWA conservation notice', 'Keep at least 5 m from wildlife, do not feed animals, no off-track driving and a maximum speed of 40 km/h. Drones require prior written UWA permission. Report snares or injured animals to any ranger.', 'info', 'Whole park', false],
        ];
        foreach ($rows as [$cat, $title, $body, $sev, $loc, $pin]) {
            TravelUpdate::updateOrCreate(['title' => $title], ['category' => $cat, 'body' => $body, 'severity' => $sev, 'location' => $loc, 'is_pinned' => $pin, 'is_active' => true]);
        }
    }
}

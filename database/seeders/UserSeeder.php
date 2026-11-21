<?php

namespace Database\Seeders;

use App\Models\Operator;
use App\Models\Park;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public const DEFAULT_PASSWORD = 'Pearl360@2026';

    public function run(): void
    {
        // Administrator (UWA) – change the password after first login!
        User::updateOrCreate(['email' => config('pearl360.admin_email')], [
            'name' => 'Pearl360 Administrator',
            'phone' => config('pearl360.admin_phone'),
            'country' => 'Uganda',
            'role' => User::ROLE_ADMIN,
            'password' => self::DEFAULT_PASSWORD,
            'email_verified_at' => now(),
        ]);

        // A second administrator – the system supports any number of admins.
        User::updateOrCreate(['email' => 'uwa.officer@pearl360.test'], [
            'name' => 'UWA Tourism Officer (Demo)',
            'phone' => '256700000002',
            'country' => 'Uganda',
            'role' => User::ROLE_ADMIN,
            'password' => self::DEFAULT_PASSWORD,
            'email_verified_at' => now(),
        ]);

        // Demo tourist
        User::updateOrCreate(['email' => 'tourist@pearl360.test'], [
            'name' => 'Demo Tourist', 'phone' => '256700000001', 'country' => 'Uganda',
            'role' => User::ROLE_TOURIST, 'password' => self::DEFAULT_PASSWORD, 'email_verified_at' => now(),
        ]);

        // Demo tour operators (fictional companies) with packages
        $operators = [
            ['email' => 'operator@pearl360.test', 'company' => 'Kob & Crane Safaris (Demo)', 'phone' => '256700000010',
                'parks' => ['queen-elizabeth-national-park', 'bwindi-impenetrable-national-park', 'lake-mburo-national-park', 'kibale-national-park', 'mgahinga-gorilla-national-park'],
                'about' => 'Demo operator for testing. Murchison Falls safaris from Kampala.',
                'packages' => [[3, '3-Day Murchison Falls Safari', 1850000, 'Top of the Falls on arrival, a dawn game drive on the Buligi tracks and the launch trip to the bottom of the falls.'],
                    [4, '4-Day Murchison Falls & Ziwa Rhinos', 2600000, 'Rhino tracking at Ziwa on the way, game drive, launch trip, delta cruise for the shoebill and the Top of the Falls.']]],
            ['email' => 'nile@pearl360.test', 'company' => 'Pearl Trails Uganda (Demo)', 'phone' => '256700000011',
                'parks' => ['murchison-falls-national-park', 'kidepo-valley-national-park', 'mount-elgon-national-park'],
                'about' => 'Demo operator for testing. Northern Uganda specialists based in Masindi.',
                'packages' => [[2, '2-Day Murchison Falls Express', 1150000, 'Launch trip to the bottom of the falls and a morning game drive.'],
                    [3, '3-Day Chimps & Falls', 2100000, 'Chimpanzee tracking at Kaniyo Pabidi, launch trip and game drive.']]],
            ['email' => 'peaks@pearl360.test', 'company' => 'Mountains of the Moon Treks (Demo)', 'phone' => '256700000012',
                'parks' => ['rwenzori-mountains-national-park', 'semuliki-national-park', 'kibale-national-park'],
                'about' => 'Demo operator for testing. Birding and walking safaris.',
                'packages' => [[3, '3-Day Shoebill & Birding Safari', 1750000, 'Delta cruise for the shoebill, guided birding walk at Paraa and a nature walk in Rabongo Forest.']]],
            ['email' => 'newoperator@pearl360.test', 'company' => 'Savannah Horizons Tours (Demo)', 'phone' => '256700000013', 'pending' => true,
                'parks' => ['kidepo-valley-national-park', 'lake-mburo-national-park'],
                'about' => 'Demo operator that has just registered and is waiting for admin approval.'],
        ];

        foreach ($operators as $o) {
            $user = User::updateOrCreate(['email' => $o['email']], [
                'name' => Str::before($o['company'], ' (').' Manager', 'phone' => $o['phone'], 'country' => 'Uganda',
                'role' => User::ROLE_OPERATOR, 'password' => self::DEFAULT_PASSWORD, 'email_verified_at' => now(),
            ]);

            $operator = Operator::updateOrCreate(['user_id' => $user->id], [
                'company_name' => $o['company'], 'slug' => Str::slug($o['company']),
                'licence_no' => 'UTB-DEMO-'.$user->id, 'contact_phone' => $o['phone'], 'contact_email' => $o['email'],
                'address' => 'Kampala, Uganda', 'about' => $o['about'], 'status' => empty($o['pending']) ? 'approved' : 'pending', 'approved_at' => empty($o['pending']) ? now() : null,
            ]);

            $parks = Park::whereIn('slug', $o['parks'])->get();
            if (! empty($o['pending'])) {
                $operator->parks()->sync($parks->pluck('id'));
                continue; // a pending operator has no packages yet
            }
            $operator->parks()->sync($parks->pluck('id'));

            $operator->packages()->delete();
            $park = Park::where('slug', config('murchison.park_slug'))->first();
            if (! $park) {
                continue;
            }
            $operator->parks()->syncWithoutDetaching([$park->id]);
            foreach ($o['packages'] ?? [] as [$days, $title, $priceUgx, $text]) {
                $operator->packages()->create([
                    'park_id' => $park->id,
                    'title' => $title,
                    'slug' => Str::slug("{$title} {$operator->company_name}"),
                    'description' => $text,
                    'includes' => "Transport in a 4x4 safari vehicle\nPark entry fees\nAccommodation & meals\nEnglish-speaking driver-guide\nDrinking water",
                    'days' => $days,
                    'price_ugx' => $priceUgx,
                    'max_group' => 6,
                    'is_active' => true,
                ]);
            }
        }
    }
}

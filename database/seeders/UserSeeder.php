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

        // Demo tourist
        User::updateOrCreate(['email' => 'tourist@pearl360.test'], [
            'name' => 'Demo Tourist', 'phone' => '256700000001', 'country' => 'Uganda',
            'role' => User::ROLE_TOURIST, 'password' => self::DEFAULT_PASSWORD, 'email_verified_at' => now(),
        ]);

        // Demo tour operators (fictional companies) with packages
        $operators = [
            ['email' => 'operator@pearl360.test', 'company' => 'Kob & Crane Safaris (Demo)', 'phone' => '256700000010',
                'parks' => ['queen-elizabeth-national-park', 'bwindi-impenetrable-national-park', 'lake-mburo-national-park', 'kibale-national-park', 'mgahinga-gorilla-national-park'],
                'about' => 'Demo operator for testing. Specialises in the south-western primate and savannah circuit.'],
            ['email' => 'nile@pearl360.test', 'company' => 'Pearl Trails Uganda (Demo)', 'phone' => '256700000011',
                'parks' => ['murchison-falls-national-park', 'kidepo-valley-national-park', 'mount-elgon-national-park'],
                'about' => 'Demo operator for testing. Northern and eastern Uganda specialists.'],
            ['email' => 'peaks@pearl360.test', 'company' => 'Mountains of the Moon Treks (Demo)', 'phone' => '256700000012',
                'parks' => ['rwenzori-mountains-national-park', 'semuliki-national-park', 'kibale-national-park'],
                'about' => 'Demo operator for testing. Hiking and birding in the Albertine Rift.'],
        ];

        foreach ($operators as $o) {
            $user = User::updateOrCreate(['email' => $o['email']], [
                'name' => Str::before($o['company'], ' (').' Manager', 'phone' => $o['phone'], 'country' => 'Uganda',
                'role' => User::ROLE_OPERATOR, 'password' => self::DEFAULT_PASSWORD, 'email_verified_at' => now(),
            ]);

            $operator = Operator::updateOrCreate(['user_id' => $user->id], [
                'company_name' => $o['company'], 'slug' => Str::slug($o['company']),
                'licence_no' => 'UTB-DEMO-'.$user->id, 'contact_phone' => $o['phone'], 'contact_email' => $o['email'],
                'address' => 'Kampala, Uganda', 'about' => $o['about'], 'is_verified' => true,
            ]);

            $parks = Park::whereIn('slug', $o['parks'])->get();
            $operator->parks()->sync($parks->pluck('id'));

            $operator->packages()->delete();
            foreach ($parks as $park) {
                $days = max(2, $park->min_days + 1);
                $price = (int) (round(usd_to_ugx($park->min_cost + 120 * $days) / 10000) * 10000);
                $operator->packages()->create([
                    'park_id' => $park->id,
                    'title' => "{$days}-Day ".Str::before($park->name, ' National Park').' Safari',
                    'slug' => Str::slug("{$days} day {$park->name} {$operator->company_name}"),
                    'description' => "A {$days}-day guided trip to {$park->name}. {$park->tagline}. Includes the park's signature experiences with an experienced driver-guide.",
                    'includes' => "Transport in a 4x4 safari vehicle\nPark entry fees\nAccommodation & meals\nEnglish-speaking driver-guide\nDrinking water",
                    'days' => $days,
                    'price_ugx' => $price,
                    'max_group' => 6,
                    'is_active' => true,
                ]);
            }
        }
    }
}

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
            'name' => 'MUBS Researcher (Admin)',
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
                'parks' => ['murchison-falls-national-park'],
                'about' => 'Demo operator for testing. Murchison Falls classic safaris from Kampala.'],
            ['email' => 'nile@pearl360.test', 'company' => 'Pearl Trails Uganda (Demo)', 'phone' => '256700000011',
                'parks' => ['murchison-falls-national-park'],
                'about' => 'Demo operator for testing. Murchison Falls boat, birding and fishing specialists.'],
            ['email' => 'peaks@pearl360.test', 'company' => 'Mountains of the Moon Treks (Demo)', 'phone' => '256700000012',
                'parks' => ['murchison-falls-national-park'],
                'about' => 'Demo operator for testing. Budongo chimpanzee and hiking trips to Murchison Falls.'],
            ['email' => 'newoperator@pearl360.test', 'company' => 'Savannah Horizons Tours (Demo)', 'phone' => '256700000013', 'pending' => true,
                'parks' => ['murchison-falls-national-park'],
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
            $park = $parks->first();
            if (! $park) {
                continue;
            }
            foreach ([[2, 'Express', 0], [3, 'Classic', 1], [4, 'Big Five & Chimps', 2]] as [$days, $label, $k]) {
                $price = (int) (round(usd_to_ugx(260 * $days + 40 * $k) / 10000) * 10000);
                $operator->packages()->create([
                    'park_id' => $park->id,
                    'title' => "{$days}-Day Murchison Falls {$label} Safari",
                    'slug' => Str::slug("{$days} day murchison {$label} {$operator->company_name}"),
                    'description' => "A {$days}-day guided trip to Murchison Falls National Park: Victoria Nile boat cruise to the base of the falls, North Bank game drive and the Top of the Falls".($days >= 4 ? ', plus Budongo chimpanzee trekking' : '').'.',
                    'includes' => "Transport in a 4x4 safari vehicle\nUWA park entry fees\nAccommodation & meals\nEnglish-speaking driver-guide\nBoat cruise & ranger guide",
                    'days' => $days,
                    'price_ugx' => $price,
                    'max_group' => 6,
                    'slots_available' => 12 - 2 * $k,
                    'available_from' => now()->startOfMonth(),
                    'available_to' => now()->addYear(),
                    'is_active' => true,
                ]);
            }
        }
    }
}

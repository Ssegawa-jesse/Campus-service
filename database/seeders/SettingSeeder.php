<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'site_tagline' => 'See Uganda\'s national parks in 360° before you go – then plan and book your safari.',
            'admin_email' => config('pearl360.admin_email'),
            'admin_phone' => config('pearl360.admin_phone'),
            'support_whatsapp' => config('pearl360.admin_phone'),
            'usd_rate' => (string) config('pearl360.usd_rate'),
            'auto_approve_reviews' => '1',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}

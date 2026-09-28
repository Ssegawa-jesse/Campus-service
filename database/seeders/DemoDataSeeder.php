<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Park;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Scene;
use App\Models\TourPackage;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\ViewLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Realistic demo activity so the analytics dashboards have something to show. */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(2026);

        $tourists = collect([User::where('email', 'tourist@pearl360.test')->first()])
            ->merge(User::factory()->count(12)->create())
            ->filter();

        $parks = Park::all();
        $weights = ['queen-elizabeth-national-park' => 10, 'bwindi-impenetrable-national-park' => 9, 'murchison-falls-national-park' => 9, 'lake-mburo-national-park' => 6, 'kibale-national-park' => 6, 'rwenzori-mountains-national-park' => 4, 'mgahinga-gorilla-national-park' => 3, 'kidepo-valley-national-park' => 3, 'mount-elgon-national-park' => 3, 'semuliki-national-park' => 2];

        // Page & 360° scene views across the last 6 months
        $logs = [];
        foreach ($parks as $park) {
            $n = ($weights[$park->slug] ?? 3) * 12;
            $sceneIds = $park->scenes()->pluck('id')->all();
            for ($i = 0; $i < $n; $i++) {
                $logs[] = ['park_id' => $park->id, 'scene_id' => mt_rand(0, 2) ? null : ($sceneIds[array_rand($sceneIds)] ?? null), 'user_id' => null, 'tour_package_id' => null, 'ip' => '127.0.0.1', 'created_at' => now()->subDays(mt_rand(0, 180))->subMinutes(mt_rand(0, 1440))];
            }
            $park->update(['views_count' => $n]);
        }
        foreach (array_chunk($logs, 200) as $chunk) {
            ViewLog::insert($chunk);
        }
        foreach (Scene::all() as $scene) {
            $scene->update(['views_count' => ViewLog::where('scene_id', $scene->id)->count()]);
        }

        // Trip planner submissions
        $interestKeys = array_keys(Park::INTERESTS);
        for ($i = 0; $i < 40; $i++) {
            $picked = collect($interestKeys)->shuffle()->take(mt_rand(1, 3))->values()->all();
            TripPlan::create([
                'user_id' => mt_rand(0, 1) ? $tourists->random()->id : null,
                'title' => 'Trip plan',
                'preferences' => ['interests' => $picked, 'fitness' => mt_rand(1, 3), 'budget' => mt_rand(3, 20) * 100, 'days' => mt_rand(2, 10), 'month' => mt_rand(1, 12)],
                'recommended_parks' => [],
                'created_at' => now()->subDays(mt_rand(0, 180)),
            ]);
        }

        // Bookings in different stages, with payments
        $packages = TourPackage::with('operator', 'park')->get();
        $statuses = ['pending', 'quoted', 'paid', 'confirmed', 'completed', 'completed', 'confirmed', 'declined', 'cancelled'];
        foreach (range(1, 28) as $i) {
            $package = $packages->random();
            $tourist = $tourists->random();
            $status = $statuses[array_rand($statuses)];
            $created = now()->subDays(mt_rand(1, 170));
            $group = mt_rand(1, 5);

            $booking = Booking::create([
                'reference' => 'P360-'.strtoupper(Str::random(6)),
                'tourist_id' => $tourist->id,
                'operator_id' => $package->operator_id,
                'park_id' => $package->park_id,
                'tour_package_id' => $package->id,
                'travel_date' => $created->copy()->addDays(mt_rand(14, 90)),
                'group_size' => $group,
                'contact_phone' => $tourist->phone,
                'message' => 'We would like to book this trip. Please send a quote.',
                'status' => $status,
                'quoted_amount' => $status === 'pending' ? null : $package->price_ugx * $group,
                'operator_response' => $status === 'pending' ? null : 'Thank you for your enquiry. Quote includes all items listed in the package.',
                'responded_at' => $status === 'pending' ? null : $created->copy()->addHours(6),
                'created_at' => $created,
                'updated_at' => $created,
            ]);

            if (in_array($status, ['paid', 'confirmed', 'completed'], true)) {
                $method = ['mobile_money_mtn', 'mobile_money_airtel', 'card'][mt_rand(0, 2)];
                Payment::create([
                    'booking_id' => $booking->id, 'user_id' => $tourist->id,
                    'tx_ref' => 'P360-TX-'.strtoupper(Str::random(10)), 'gateway' => 'simulate', 'method' => $method,
                    'phone' => $tourist->phone, 'amount' => $booking->quoted_amount, 'currency' => 'UGX',
                    'status' => 'successful', 'gateway_reference' => 'SIM-'.strtoupper(Str::random(8)),
                    'paid_at' => $created->copy()->addDay(), 'created_at' => $created->copy()->addDay(),
                ]);
            }
        }

        // Reviews
        $comments = [
            'The 360° tour showed exactly what to expect – the real thing was even better!',
            'Amazing experience, our guide was knowledgeable and friendly.',
            'Well organised trip. Roads were a bit rough but totally worth it.',
            'Saw so much wildlife. Highly recommend the boat cruise.',
            'Breathtaking scenery. Bring good hiking boots!',
            'Great value and the booking through Pearl360 was easy with Mobile Money.',
        ];
        foreach ($parks as $park) {
            foreach ($tourists->random(min(3, $tourists->count())) as $t) {
                Review::create(['user_id' => $t->id, 'park_id' => $park->id, 'rating' => mt_rand(4, 5), 'comment' => $comments[array_rand($comments)], 'is_approved' => true]);
            }
        }
    }
}

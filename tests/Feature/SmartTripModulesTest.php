<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Booking;
use App\Models\EmailLog;
use App\Models\Hotspot;
use App\Models\Itinerary;
use App\Models\Place;
use App\Models\Scene;
use App\Models\SmsLog;
use App\Models\TravelUpdate;
use App\Models\User;
use App\Services\Murchison\BudgetCalculator;
use App\Services\Murchison\ItineraryEngine;
use App\Services\Murchison\RouteService;
use App\Services\Murchison\TravelAssistant;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Tests for the six Smart Trip Planning modules and the itinerary → booking → payment → notification flow. */
class SmartTripModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('SEED_DEMO_DATA=false');
        $this->seed(DatabaseSeeder::class);
    }

    private function engine(): ItineraryEngine
    {
        return app(ItineraryEngine::class);
    }

    private function scheduled(array $plan): array
    {
        return collect($plan['days'])->flatMap(fn ($d) => collect($d['items'])->where('kind', 'activity')->pluck('activity'))->all();
    }

    // ---- Module 1: Smart Itinerary --------------------------------------------------------------

    public function test_itinerary_has_one_entry_per_day_with_travel_on_first_and_last_day(): void
    {
        $plan = $this->engine()->build(['days' => 3, 'start_date' => '2027-07-10', 'interests' => ['wildlife', 'boat', 'waterfalls']]);

        $this->assertCount(3, $plan['days']);
        $this->assertSame('travel', $plan['days'][0]['items'][0]['kind']);
        $this->assertContains('travel', collect($plan['days'][2]['items'])->pluck('kind')->all());
        $this->assertContains('victoria-nile-boat-cruise', $this->scheduled($plan));
        $this->assertContains('north-bank-game-drive', $this->scheduled($plan));
        $this->assertSame('Dry', $plan['season']['season']);
    }

    public function test_north_bank_activities_add_a_paraa_ferry_crossing(): void
    {
        $plan = $this->engine()->build(['days' => 2, 'interests' => ['wildlife'], 'must_do' => ['north-bank-game-drive']]);
        $this->assertContains('ferry', collect($plan['days'])->flatMap(fn ($d) => collect($d['items'])->pluck('kind'))->all());
        $vehicle = collect($plan['budget']['lines'])->firstWhere('key', 'vehicle');
        $this->assertStringContainsString('ferry', $vehicle['detail']);
    }

    public function test_chimps_only_when_interested_and_fit_enough(): void
    {
        $this->assertNotContains('budongo-chimpanzee-trekking', $this->scheduled($this->engine()->build(['days' => 4, 'interests' => ['wildlife']])));
        $this->assertContains('budongo-chimpanzee-trekking', $this->scheduled($this->engine()->build(['days' => 4, 'interests' => ['chimps'], 'fitness' => 2])));

        $low = $this->engine()->build(['days' => 4, 'interests' => ['chimps'], 'fitness' => 1]);
        $this->assertNotContains('budongo-chimpanzee-trekking', $this->scheduled($low));
        $this->assertNotEmpty($low['skipped']);
    }

    public function test_must_do_from_hotspot_is_always_scheduled(): void
    {
        $plan = $this->engine()->build(['days' => 3, 'interests' => ['birds'], 'fitness' => 1, 'must_do' => ['sport-fishing']]);
        $this->assertContains('sport-fishing', $this->scheduled($plan));
        $this->assertContains('Sport Fishing below the Falls: you asked for it.', $plan['reasons']);
    }

    public function test_night_drive_uses_the_night_slot_and_relaxed_pace_limits_activities(): void
    {
        $plan = $this->engine()->build(['days' => 3, 'interests' => ['night', 'wildlife']]);
        $night = collect($plan['days'])->flatMap(fn ($d) => $d['items'])->firstWhere('activity', 'night-game-drive');
        $this->assertSame('19:30', $night['time']);

        $relaxed = $this->engine()->build(['days' => 3, 'pace' => 'relaxed', 'interests' => array_keys(config('murchison.interests'))]);
        foreach ($relaxed['days'] as $d) {
            $this->assertLessThanOrEqual(2, collect($d['items'])->where('kind', 'activity')->count());
        }
    }

    public function test_budget_limit_warning(): void
    {
        $plan = $this->engine()->build(['days' => 3, 'budget_limit' => 100000]);
        $this->assertTrue(collect($plan['warnings'])->contains(fn ($w) => str_contains($w, 'above your budget')));
    }

    // ---- Module 2: Travel Budgeting (UWA tiers) -------------------------------------------------

    public function test_uwa_tiers_use_usd_for_foreigners_and_ugx_for_east_africans(): void
    {
        $calc = app(BudgetCalculator::class);
        $base = ['days' => 2, 'adults' => 2, 'children' => 0, 'tier' => 'budget', 'transport' => 'arranged', 'origin' => 'kampala', 'activities' => ['victoria-nile-boat-cruise']];
        $rate = (float) setting('usd_rate', config('pearl360.usd_rate'));

        $fnr = collect($calc->calculate($base + ['category' => 'fnr'])['lines'])->keyBy('key');
        $eac = collect($calc->calculate($base + ['category' => 'eac'])['lines'])->keyBy('key');

        $this->assertEquals(round(45 * $rate * 2 * 2), $fnr['entry']['ugx']);
        $this->assertEquals(25000 * 2 * 2, $eac['entry']['ugx']);
        $this->assertEquals(round(35 * $rate) * 2, $fnr['activities']['ugx']);
        $this->assertEquals(30000 * 2, $eac['activities']['ugx']);
        $this->assertLessThan($fnr['entry']['ugx'], $eac['entry']['ugx']);
    }

    public function test_children_are_excluded_from_chimp_permits_and_pay_half_entry(): void
    {
        $calc = app(BudgetCalculator::class);
        $chimps = Activity::where('slug', 'budongo-chimpanzee-trekking')->first();
        $this->assertSame($calc->activityCost($chimps, 2, 0, 'eac'), $calc->activityCost($chimps, 2, 3, 'eac'));

        $r = $calc->calculate(['days' => 1, 'adults' => 1, 'children' => 2, 'category' => 'eac', 'tier' => 'budget', 'transport' => 'arranged', 'origin' => 'kampala', 'activities' => []]);
        $this->assertEquals(25000 * 2, collect($r['lines'])->firstWhere('key', 'entry')['ugx']);
        $this->assertSame($r['subtotal'] + $r['contingency'], $r['total']);
    }

    public function test_budget_page_recalculates_from_query(): void
    {
        $this->get('/budget?category=eac&days=2&adults=1&tier=budget&transport=arranged&activities[]=sport-fishing')
            ->assertOk()->assertSee('East African Citizen')->assertSee('Sport Fishing');
    }

    // ---- Module 3: Smart Navigation -------------------------------------------------------------

    public function test_route_between_gate_and_jetty_and_ferry_detection(): void
    {
        $routes = app(RouteService::class);
        $gate = Place::where('slug', 'kichumbanyobo-gate')->first();
        $jetty = Place::where('slug', 'paraa-jetty')->first();
        $airfield = Place::where('slug', 'pakuba-airfield')->first();

        $r = $routes->route($gate, $jetty);
        $this->assertGreaterThan(30, $r['road_km']);
        $this->assertLessThan(80, $r['road_km']);
        $this->assertFalse($r['ferry']);
        $this->assertTrue($routes->route($jetty, $airfield)['ferry']);

        $this->getJson("/navigate/route?from={$gate->id}&to={$jetty->id}")->assertOk()->assertJsonStructure(['road_km', 'minutes', 'ferry', 'summary']);
        $this->getJson("/navigate/route?from={$gate->id}&to={$gate->id}")->assertStatus(422);
    }

    // ---- Module 4: AI Travel Assistant ----------------------------------------------------------

    public function test_assistant_answers_from_the_knowledge_base_and_logs_questions(): void
    {
        config(['services.anthropic.key' => null]);
        $this->postJson('/assistant/ask', ['question' => 'What should I pack?'])->assertOk()->assertJson(['source' => 'knowledge-base', 'intent' => 'packing']);
        $this->postJson('/assistant/ask', ['question' => 'When does the Paraa ferry run?'])->assertOk()->assertJsonPath('intent', 'ferry');
        $this->postJson('/assistant/ask', ['question' => 'What are the UWA rules?'])->assertOk()->assertJsonPath('intent', 'rules');

        $r = $this->postJson('/assistant/ask', ['question' => 'Plan 3 days with $900 for chimps'])->assertOk()->json();
        $this->assertSame('itinerary', $r['intent']);
        $this->assertStringContainsString('Day 3', $r['answer']);
        $this->assertNotNull($r['plan_link']);

        $this->assertSame(4, \App\Models\AssistantLog::count());
        $this->assertStringContainsString('Murchison Falls', app(TravelAssistant::class)->systemPrompt());
    }

    // ---- Module 5: Travel Updates ---------------------------------------------------------------

    public function test_admin_publishes_travel_update_and_expired_ones_are_hidden(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin)->post('/admin/travel-updates', [
            'category' => 'ferry', 'severity' => 'critical', 'title' => 'Paraa ferry suspended for repairs', 'body' => 'No crossings today.', 'is_active' => 1,
        ])->assertRedirect();
        $this->get('/updates')->assertSee('Paraa ferry suspended for repairs');

        TravelUpdate::create(['category' => 'road', 'severity' => 'info', 'title' => 'Old road notice', 'body' => 'x', 'is_active' => true, 'valid_until' => now()->subDay()]);
        $this->get('/updates')->assertDontSee('Old road notice');
        $this->assertTrue(TravelUpdate::current()->where('severity', 'critical')->exists());
    }

    // ---- Module 6: HP5 360° studio + Add to itinerary --------------------------------------------

    public function test_researcher_edits_hotspot_and_tourist_adds_activity_from_hotspot(): void
    {
        $admin = User::where('email', '2400725284@mubs.ac.ug')->first();
        $this->assertTrue($admin->isAdmin());
        $scene = Scene::whereNotNull('activity_id')->first();
        $activity = Activity::where('slug', 'night-game-drive')->first();

        $this->actingAs($admin)->post("/admin/scenes/{$scene->id}/hotspots", [
            'type' => 'info', 'pitch' => 5.5, 'yaw' => -20, 'title' => 'Spotlight point', 'info_text' => 'Leopards seen here', 'note' => 'Captured 06:40', 'activity_id' => $activity->id,
        ])->assertSessionHas('success');
        $hotspot = Hotspot::where('title', 'Spotlight point')->firstOrFail();
        $this->actingAs($admin)->put("/admin/hotspots/{$hotspot->id}", ['type' => 'info', 'pitch' => 1, 'yaw' => 2, 'title' => 'Spotlight point (edited)', 'activity_id' => $activity->id])->assertSessionHas('success');
        $this->assertSame('Spotlight point (edited)', $hotspot->fresh()->title);
        $this->assertSame('night-game-drive', $hotspot->fresh()->toViewer()['activity']['slug']);

        auth()->logout();
        $this->postJson("/itinerary/add/{$activity->slug}")->assertOk()->assertJson(['count' => 1]);
        $this->get('/itinerary')->assertSee('Must-do activities you added');
    }

    // ---- End-to-end: itinerary → save → book → pay (MoMo) → email + SMS ---------------------------

    public function test_tourist_saves_books_and_pays_for_an_itinerary_with_confirmations(): void
    {
        $tourist = User::where('email', 'tourist@pearl360.test')->first();
        $this->actingAs($tourist)->post('/itinerary/generate', [
            'start_date' => now()->addMonth()->toDateString(), 'days' => 3, 'adults' => 2, 'children' => 0, 'category' => 'eac',
            'interests' => ['wildlife', 'boat'], 'fitness' => 2, 'tier' => 'mid', 'transport' => 'tour', 'origin' => 'kampala', 'pace' => 'full',
        ])->assertOk()->assertSee('Day 3');

        $this->actingAs($tourist)->post('/itinerary/save', ['title' => 'Family trip'])->assertRedirect();
        $it = Itinerary::where('title', 'Family trip')->firstOrFail();
        $this->actingAs($tourist)->get("/itinerary/{$it->reference}")->assertOk()->assertSee('Book & pay', false);

        $operator = \App\Models\Operator::where('is_verified', true)->first();
        $this->actingAs($tourist)->post("/itinerary/{$it->reference}/book", ['operator_id' => $operator->id, 'contact_phone' => '0741141433'])->assertRedirect();
        $booking = Booking::where('itinerary_id', $it->id)->firstOrFail();
        $this->assertSame('quoted', $booking->status);
        $this->assertSame((int) $it->total_ugx, (int) $booking->quoted_amount);

        $this->actingAs($tourist)->post("/bookings/{$booking->reference}/pay", ['method' => 'mobile_money_mtn', 'phone' => '0741141433']);
        $payment = $booking->payments()->latest()->first();
        $this->actingAs($tourist)->post("/payments/simulate/{$payment->tx_ref}", ['decision' => 'approve', 'pin' => '1234']);
        $this->assertSame('paid', $booking->fresh()->status);

        $this->assertTrue(EmailLog::where('to', '2400725284@mubs.ac.ug')->where('subject', 'like', 'Payment received%')->exists());
        $this->assertTrue(SmsLog::where('to', '+256741141433')->where('message', 'like', '%received%')->exists());

        $other = User::where('email', 'like', '%@pearl360.test')->where('role', 'tourist')->where('id', '!=', $tourist->id)->first()
            ?? User::factory()->create(['role' => 'tourist']);
        $this->actingAs($other)->get("/itinerary/{$it->reference}")->assertForbidden();
    }

    public function test_admin_notification_simulator_logs_sms_and_email(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin)->post('/admin/notifications/test', ['channel' => 'both', 'email' => '2400725284@mubs.ac.ug', 'phone' => '+256741141433'])->assertSessionHas('success');
        $this->assertTrue(SmsLog::where('to', '+256741141433')->exists());
        $this->assertTrue(EmailLog::where('to', '2400725284@mubs.ac.ug')->exists());
        $this->actingAs($admin)->get('/admin/notifications')->assertOk()->assertSee('+256741141433');
    }

    public function test_admin_updates_uwa_prices(): void
    {
        $admin = User::where('role', 'admin')->first();
        $a = Activity::where('slug', 'victoria-nile-boat-cruise')->first();
        $this->actingAs($admin)->put("/admin/uwa-activities/{$a->slug}", [
            'name' => $a->name, 'category' => 'boat', 'place_id' => $a->place_id, 'description' => $a->description,
            'price_fnr_usd' => 40, 'price_fr_usd' => 32, 'price_eac_ugx' => 35000, 'hours' => 3, 'fitness_level' => 1, 'priority' => 10,
            'slots' => ['afternoon', 'morning'], 'interests' => ['boat'], 'is_active' => 1,
        ])->assertSessionHas('success');
        $this->assertSame(35000, (int) $a->fresh()->price_eac_ugx);
        $this->assertSame('US$40', $a->fresh()->priceLabel('fnr'));
    }
}

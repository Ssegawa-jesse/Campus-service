<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Operator;
use App\Models\Park;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_only_murchison_falls_is_seeded_with_the_research_activities(): void
    {
        $this->assertSame(1, Park::count());
        $park = Park::first();
        $this->assertSame('murchison-falls-national-park', $park->slug);
        foreach (['victoria-nile-boat-cruise', 'north-bank-game-drive', 'hike-to-the-top-of-the-falls', 'budongo-chimpanzee-trekking',
            'albert-delta-shoebill-birding', 'sport-fishing', 'night-game-drive'] as $slug) {
            $a = \App\Models\Activity::where('slug', $slug)->firstOrFail();
            $this->assertGreaterThanOrEqual(1, $a->scenes()->count(), $slug);
        }
        foreach (['kichumbanyobo-gate', 'bugungu-gate', 'tangi-gate', 'chobe-gate', 'wankwar-gate', 'paraa-jetty', 'bakers-trail', 'pakuba-airfield'] as $slug) {
            $this->assertTrue(\App\Models\Place::where('slug', $slug)->exists(), $slug);
        }
    }

    public function test_public_pages_load(): void
    {
        $park = Park::first();
        $package = \App\Models\TourPackage::first();

        foreach (['/', '/about', '/contact', '/park', "/parks/{$park->slug}", '/itinerary', '/budget', '/budget?category=eac&days=2&activities[]=sport-fishing',
            '/navigate', '/assistant', '/updates', '/explore', '/explore?activity=night-game-drive', '/packages',
            "/packages/{$package->slug}", "/operators/{$package->operator->slug}", '/login', '/register'] as $url) {
            $this->get($url)->assertOk();
        }
        foreach (['/parks', '/map', '/planner', "/parks/{$park->slug}/tour"] as $url) {
            $this->get($url)->assertRedirect();
        }
    }

    public function test_role_protected_areas(): void
    {
        $tourist = User::where('email', 'tourist@pearl360.test')->first();
        $admin = User::where('role', 'admin')->first();

        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs($tourist)->get('/admin')->assertForbidden();
        $this->actingAs($tourist)->get('/operator')->assertForbidden();
        $this->actingAs($admin)->get('/my')->assertForbidden();
    }

    public function test_every_dashboard_page_renders(): void
    {
        $this->seed(\Database\Seeders\DemoDataSeeder::class);
        $admin = User::where('role', 'admin')->first();
        $park = Park::first();
        $scene = $park->scenes()->first();

        foreach (['/admin', '/admin/parks', '/admin/parks/create', "/admin/parks/{$park->slug}/edit", "/admin/parks/{$park->slug}/media",
            "/admin/scenes/{$scene->id}/edit", '/admin/studio', '/admin/uwa-activities', '/admin/places', '/admin/places/create',
            '/admin/travel-updates', '/admin/travel-updates/create', '/admin/notifications', '/admin/assistant-logs', '/admin/itineraries', '/admin/operators', '/admin/users', '/admin/users/create',
            '/admin/bookings', '/admin/payments', '/admin/reviews', '/admin/sms', '/admin/messages', '/admin/settings', '/admin/reports/export', '/profile'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $operatorUser = User::where('email', 'operator@pearl360.test')->first();
        $booking = $operatorUser->operator->bookings()->first();
        foreach (['/operator', '/operator/bookings', "/operator/bookings/{$booking->reference}", '/operator/packages', '/operator/packages/create', '/operator/profile'] as $url) {
            $this->actingAs($operatorUser)->get($url)->assertOk();
        }

        $tourist = User::where('email', 'tourist@pearl360.test')->first();
        foreach (['/my', '/my/bookings', '/my/bookings/new', "/my/bookings/new?park={$park->slug}", '/itinerary'] as $url) {
            $this->actingAs($tourist)->get($url)->assertOk();
        }
    }

    public function test_registration_as_operator_creates_unverified_operator(): void
    {
        $this->post('/register', [
            'role' => 'operator', 'name' => 'Jane Guide', 'email' => 'jane@example.com', 'phone' => '0772000000',
            'company_name' => 'Jane Safaris', 'password' => 'secret1234', 'password_confirmation' => 'secret1234',
        ])->assertRedirect(route('operator.dashboard'));

        $this->assertFalse(Operator::where('company_name', 'Jane Safaris')->first()->is_verified);
        $this->assertDatabaseHas('sms_logs', ['to' => '+256741141433']); // admin alerted
    }
}

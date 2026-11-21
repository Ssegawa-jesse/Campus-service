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

    public function test_only_murchison_falls_is_seeded_with_activity_scenes(): void
    {
        $this->assertSame(1, Park::count());
        $park = Park::first();
        $this->assertSame(config('murchison.park_slug'), $park->slug);
        $this->assertGreaterThanOrEqual(3, $park->scenes()->count());
        $this->assertSame(['game-drive', 'launch', 'top-falls'], $park->scenes()->whereNotNull('activity_key')->pluck('activity_key')->sort()->values()->all());
        $this->assertSame(count(config('murchison.activities')), $park->activities()->count());
    }

    public function test_public_pages_load(): void
    {
        $park = Park::first();
        $package = \App\Models\TourPackage::first();

        foreach (['/', '/plan', '/budget', '/budget?days=4&adults=2&children=1&residency=eac&tier=budget&transport=self&origin=masindi&activities[]=chimps',
            '/map', '/map?ids=buligi,paraa', '/explore', '/park', '/assistant', '/updates', '/about', '/contact',
            "/parks/{$park->slug}", "/parks/{$park->slug}/tour", '/packages',
            "/packages/{$package->slug}", "/operators/{$package->operator->slug}", '/login', '/register'] as $url) {
            $this->get($url)->assertOk();
        }
        foreach (['/parks' => '/park', '/planner' => '/plan', '/parks/compare' => '/plan'] as $old => $new) {
            $this->get($old)->assertRedirect($new);
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
            "/admin/parks/{$park->slug}/scenes", "/admin/scenes/{$scene->id}/edit", '/admin/operators', '/admin/users', '/admin/users/create',
            '/admin/bookings', '/admin/payments', '/admin/reviews', '/admin/sms', '/admin/messages', '/admin/settings', '/admin/reports/export', '/profile'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $operatorUser = User::where('email', 'operator@pearl360.test')->first();
        $booking = $operatorUser->operator->bookings()->first();
        foreach (['/operator', '/operator/bookings', "/operator/bookings/{$booking->reference}", '/operator/packages', '/operator/packages/create', '/operator/profile'] as $url) {
            $this->actingAs($operatorUser)->get($url)->assertOk();
        }

        $tourist = User::where('email', 'tourist@pearl360.test')->first();
        foreach (['/my', '/my/bookings', '/my/bookings/new', "/my/bookings/new?park={$park->slug}"] as $url) {
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

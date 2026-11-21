<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Operator;
use App\Models\User;
use App\Services\Murchison\SmartItinerary;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartTripTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function query(array $over = []): array
    {
        return array_merge(['build' => 1, 'start_date' => now()->addDays(20)->toDateString(), 'days' => 3, 'adults' => 2, 'children' => 0,
            'residency' => 'fnr', 'interests' => ['wildlife', 'waterfalls', 'boat'], 'fitness' => 2, 'tier' => 'mid', 'transport' => 'tour',
            'origin' => 'kampala', 'pace' => 'full'], $over);
    }

    public function test_the_planner_builds_an_itinerary_with_360_links(): void
    {
        $this->get('/plan?'.http_build_query($this->query()))
            ->assertOk()
            ->assertSee('Game drive on the Buligi tracks')
            ->assertSee('Launch trip to the bottom of the falls')
            ->assertSee('Estimated budget')
            ->assertSee('View in 360°')
            ->assertSee('Book this itinerary');
    }

    public function test_the_planner_requires_an_interest_and_a_future_date(): void
    {
        $this->get('/plan?'.http_build_query($this->query(['interests' => []])))->assertOk()->assertSee('Choose at least one interest.');
        $this->get('/plan?'.http_build_query($this->query(['start_date' => '2020-01-01'])))->assertRedirect();
    }

    public function test_a_shared_link_opens_the_same_itinerary(): void
    {
        $code = SmartItinerary::encode(SmartItinerary::normalize($this->query(['days' => 4, 'must_do' => ['delta']])));
        $this->get('/plan?p='.$code)->assertOk()->assertSee('Delta cruise for the shoebill')->assertSee('Day 4');
    }

    public function test_tourists_save_and_book_their_itinerary_and_the_operator_sees_it(): void
    {
        $tourist = User::where('role', 'tourist')->first();
        $code = SmartItinerary::encode(SmartItinerary::normalize($this->query()));

        $this->actingAs($tourist)->post('/my/plans', ['p' => $code])->assertRedirect();
        $this->assertSame(1, $tourist->tripPlans()->count());
        $this->actingAs($tourist)->get('/my')->assertOk()->assertSee('Book this itinerary');

        $this->actingAs($tourist)->get('/my/bookings/new?plan='.$code)->assertOk()->assertSee('Your smart itinerary will be sent with this enquiry');

        $operator = Operator::where('is_verified', true)->first();
        $this->actingAs($tourist)->post('/my/bookings', [
            'park_id' => $operator->parks()->first()->id ?? \App\Http\Controllers\SmartTripController::park()->id,
            'operator_id' => $operator->id, 'travel_date' => now()->addDays(20)->toDateString(), 'group_size' => 2,
            'contact_phone' => '0772123456', 'message' => 'Please quote', 'itinerary' => $code,
        ])->assertRedirect();

        $booking = Booking::latest('id')->first();
        $this->assertSame($code, $booking->itinerary);
        $this->actingAs($operator->user)->get("/operator/bookings/{$booking->reference}")->assertOk()->assertSee('Smart itinerary sent with this booking');
    }

    public function test_the_assistant_answers_without_an_ai_key(): void
    {
        config(['services.anthropic.key' => null]);
        $this->postJson('/assistant/ask', ['messages' => [['role' => 'user', 'content' => 'How much is the boat trip?']]])
            ->assertOk()->assertJson(['source' => 'guide'])->assertJsonPath('reply', fn ($r) => str_contains($r, 'Launch trip'));
        $this->postJson('/assistant/ask', ['messages' => []])->assertStatus(422);
    }

    public function test_admins_link_360_scenes_to_activities(): void
    {
        $admin = User::where('role', 'admin')->first();
        $scene = \App\Http\Controllers\SmartTripController::park()->scenes()->first();
        $this->actingAs($admin)->put("/admin/scenes/{$scene->id}", ['title' => $scene->title, 'activity_key' => 'chimps'])->assertRedirect();
        $this->assertSame('chimps', $scene->fresh()->activity_key);
        $this->actingAs($admin)->put("/admin/scenes/{$scene->id}", ['title' => $scene->title, 'activity_key' => 'not-an-activity'])->assertSessionHasErrors('activity_key');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'student_name' => 'Aisha Namuli',
            'registration_number' => '22/U/5432/PS',
            'programme' => 'Bachelor of Business Computing',
            'book_title' => 'Database System Concepts',
            'pickup_date' => now()->addDay()->toDateString(),
        ], $overrides);
    }

    public function test_home_page_uses_the_layout(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Skip the front desk queue.')
            ->assertSee('site-header', false)
            ->assertSee('site-footer', false);
    }

    public function test_reservation_pages_are_reachable(): void
    {
        $this->get(route('reservations.index'))->assertOk()->assertSee('No reservations yet');
        $this->get(route('reservations.create'))->assertOk()->assertSee('name="_token"', false);
    }

    public function test_a_reservation_is_saved_and_listed(): void
    {
        $this->post(route('reservations.store'), $this->validData())
            ->assertRedirect(route('reservations.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reservations', [
            'registration_number' => '22/U/5432/PS',
            'book_title' => 'Database System Concepts',
        ]);

        $this->get(route('reservations.index'))
            ->assertSee('Aisha Namuli')
            ->assertSee('Reservation saved.');
    }

    public function test_invalid_submissions_are_rejected(): void
    {
        $this->from(route('reservations.create'))
            ->post(route('reservations.store'), $this->validData([
                'student_name' => '',
                'programme' => 'Not a programme',
                'pickup_date' => now()->subDay()->toDateString(),
            ]))
            ->assertRedirect(route('reservations.create'))
            ->assertSessionHasErrors(['student_name', 'programme', 'pickup_date']);

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_a_reservation_can_be_updated(): void
    {
        $reservation = Reservation::factory()->create();

        $this->get(route('reservations.edit', $reservation))->assertOk()->assertSee($reservation->student_name);

        $this->put(route('reservations.update', $reservation), $this->validData(['book_title' => 'Clean Code']))
            ->assertRedirect(route('reservations.index'));

        $this->assertSame('Clean Code', $reservation->fresh()->book_title);
    }

    public function test_a_reservation_can_be_deleted(): void
    {
        $reservation = Reservation::factory()->create();

        $this->delete(route('reservations.destroy', $reservation))
            ->assertRedirect(route('reservations.index'));

        $this->assertModelMissing($reservation);
    }
}

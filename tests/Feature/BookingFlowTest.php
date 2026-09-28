<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\TourPackage;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_enquiry_quote_mobile_money_payment_and_confirmation(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tourist = User::where('email', 'tourist@pearl360.test')->first();
        $package = TourPackage::with('operator.user')->first();
        $operatorUser = $package->operator->user;

        // 1. Tourist sends an enquiry
        $this->actingAs($tourist)->post('/my/bookings', [
            'park_id' => $package->park_id, 'operator_id' => $package->operator_id, 'tour_package_id' => $package->id,
            'travel_date' => now()->addMonth()->toDateString(), 'group_size' => 2, 'contact_phone' => '0700000001',
        ])->assertRedirect();
        $booking = Booking::latest('id')->first();
        $this->assertSame('pending', $booking->status);
        $this->assertDatabaseHas('sms_logs', ['to' => '+256700000010']); // operator alerted

        // Tourist cannot pay before a quote
        $this->actingAs($tourist)->get("/bookings/{$booking->reference}/pay")->assertRedirect();

        // 2. Operator sends a quote
        $this->actingAs($operatorUser)->put("/operator/bookings/{$booking->reference}", [
            'status' => 'quoted', 'quoted_amount' => 2500000, 'operator_response' => 'All good',
        ])->assertRedirect();
        $this->assertSame('quoted', $booking->fresh()->status);

        // Another tourist cannot see or pay this booking
        $other = User::factory()->create();
        $this->actingAs($other)->get("/my/bookings/{$booking->reference}")->assertForbidden();

        // 3. Tourist pays with MTN MoMo (simulator)
        $this->actingAs($tourist)->post("/bookings/{$booking->reference}/pay", ['method' => 'mobile_money_mtn', 'phone' => '0770000001'])
            ->assertRedirect();
        $payment = Payment::latest('id')->first();
        $this->actingAs($tourist)->get("/payments/simulate/{$payment->tx_ref}")->assertOk();
        $this->actingAs($tourist)->post("/payments/simulate/{$payment->tx_ref}", ['decision' => 'approve', 'pin' => '1234'])->assertRedirect();

        $this->assertSame('successful', $payment->fresh()->status);
        $this->assertSame('paid', $booking->fresh()->status);
        $this->assertDatabaseHas('sms_logs', ['to' => '+256741141433']); // admin payment alert

        // 4. Operator confirms; cannot jump backwards
        $this->actingAs($operatorUser)->put("/operator/bookings/{$booking->reference}", ['status' => 'quoted', 'quoted_amount' => 1000])
            ->assertSessionHasErrors('status');
        $this->actingAs($operatorUser)->put("/operator/bookings/{$booking->reference}", ['status' => 'confirmed'])->assertRedirect();
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_declined_payment_keeps_booking_payable(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tourist = User::where('email', 'tourist@pearl360.test')->first();
        $package = TourPackage::first();
        $booking = Booking::create([
            'tourist_id' => $tourist->id, 'operator_id' => $package->operator_id, 'park_id' => $package->park_id,
            'travel_date' => now()->addWeek(), 'group_size' => 1, 'contact_phone' => '0700000001', 'status' => 'quoted', 'quoted_amount' => 100000,
        ]);

        $this->actingAs($tourist)->post("/bookings/{$booking->reference}/pay", ['method' => 'card']);
        $payment = Payment::latest('id')->first();
        $this->actingAs($tourist)->post("/payments/simulate/{$payment->tx_ref}", ['decision' => 'decline']);

        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertTrue($booking->fresh()->canBePaid());
    }
}

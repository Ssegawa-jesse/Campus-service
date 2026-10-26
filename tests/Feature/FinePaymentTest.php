<?php

namespace Tests\Feature;

use App\Models\Copy;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function returnedLateFine(Member $member): Fine
    {
        $loan = Loan::create([
            'copy_id' => Copy::factory()->create()->id, 'member_id' => $member->id,
            'issued_at' => now()->subDays(20), 'due_at' => now()->subDays(6), 'returned_at' => now(),
        ]);

        return Fine::create(['loan_id' => $loan->id, 'member_id' => $member->id, 'amount' => 3000, 'status' => 'unpaid']);
    }

    public function test_member_pays_fine_with_mobile_money_in_sandbox(): void
    {
        $member = Member::factory()->create();
        $fine = $this->returnedLateFine($member);

        $response = $this->actingAs($member->user)->post("/my/fines/{$fine->id}/pay", ['method' => 'mtn', 'phone' => '0772123456']);
        $payment = Payment::first();
        $response->assertRedirect(route('payments.sandbox', $payment));

        $this->actingAs($member->user)->post("/payments/{$payment->id}/sandbox", ['outcome' => 'approve'])
            ->assertRedirect(route('my.payments.receipt', $payment));

        $this->assertSame('paid', $fine->fresh()->status);
        $this->assertSame('successful', $payment->fresh()->status);
        $this->actingAs($member->user)->get(route('my.payments.receipt', $payment))->assertOk()->assertSee($payment->reference);
    }

    public function test_declined_payment_leaves_fine_unpaid(): void
    {
        $member = Member::factory()->create();
        $fine = $this->returnedLateFine($member);
        $this->actingAs($member->user)->post("/my/fines/{$fine->id}/pay", ['method' => 'airtel', 'phone' => '0701234567']);

        $this->actingAs($member->user)->post('/payments/'.Payment::first()->id.'/sandbox', ['outcome' => 'decline']);

        $this->assertSame('unpaid', $fine->fresh()->status);
        $this->assertSame('failed', Payment::first()->status);
    }

    public function test_cannot_pay_another_members_fine(): void
    {
        $fine = $this->returnedLateFine(Member::factory()->create());

        $this->actingAs(Member::factory()->create()->user)->post("/my/fines/{$fine->id}/pay", ['method' => 'mtn', 'phone' => '0772123456'])->assertForbidden();
    }

    public function test_librarian_can_waive_or_record_cash(): void
    {
        $librarian = User::factory()->librarian()->create();
        $member = Member::factory()->create();
        $a = $this->returnedLateFine($member);
        $b = $this->returnedLateFine($member);

        $this->actingAs($librarian)->post("/admin/fines/{$a->id}/waive", ['reason' => 'Hospitalised'])->assertSessionHasNoErrors();
        $this->actingAs($librarian)->post("/admin/fines/{$b->id}/cash")->assertSessionHasNoErrors();

        $this->assertSame('waived', $a->fresh()->status);
        $this->assertSame('paid', $b->fresh()->status);
        $this->assertSame('cash', Payment::first()->method);
    }
}

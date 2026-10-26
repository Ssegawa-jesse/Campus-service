<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Copy;
use App\Models\Fine;
use App\Models\LibraryNotification;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Reservation;
use App\Models\User;
use App\Support\LibrarySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CirculationTest extends TestCase
{
    use RefreshDatabase;

    protected User $librarian;

    protected function setUp(): void
    {
        parent::setUp();
        LibrarySettings::flush();
        $this->librarian = User::factory()->librarian()->create();
    }

    protected function issue(Member $member, Copy $copy)
    {
        return $this->actingAs($this->librarian)->post('/admin/circulation/issue', ['member_no' => $member->member_no, 'copy_code' => $copy->code]);
    }

    public function test_issue_sets_due_date_by_member_type(): void
    {
        $student = Member::factory()->create();
        $staff = Member::factory()->staff()->create();
        [$a, $b] = Copy::factory()->count(2)->create();

        $this->issue($student, $a)->assertSessionHasNoErrors();
        $this->issue($staff, $b)->assertSessionHasNoErrors();

        $this->assertTrue(Loan::where('copy_id', $a->id)->first()->due_at->isSameDay(now()->addDays(14)));
        $this->assertTrue(Loan::where('copy_id', $b->id)->first()->due_at->isSameDay(now()->addDays(30)));
        $this->assertSame('on_loan', $a->fresh()->status);
    }

    public function test_borrowing_limit_is_enforced(): void
    {
        $member = Member::factory()->create(['max_loans' => 1]);
        [$a, $b] = Copy::factory()->count(2)->create();

        $this->issue($member, $a)->assertSessionHasNoErrors();
        $this->issue($member, $b)->assertSessionHasErrors('member');
        $this->assertSame('available', $b->fresh()->status);
    }

    public function test_cannot_issue_a_copy_already_on_loan(): void
    {
        $copy = Copy::factory()->create();
        $this->issue(Member::factory()->create(), $copy);

        $this->issue(Member::factory()->create(), $copy)->assertSessionHasErrors('copy');
    }

    public function test_unpaid_fines_above_threshold_block_borrowing(): void
    {
        $member = Member::factory()->create();
        Fine::create(['member_id' => $member->id, 'amount' => 5000, 'status' => 'unpaid']);

        $this->issue($member, Copy::factory()->create())->assertSessionHasErrors('member');
    }

    public function test_late_return_creates_fine(): void
    {
        $member = Member::factory()->create();
        $copy = Copy::factory()->create();
        $this->issue($member, $copy);

        $this->travel(17)->days(); // 3 days late at UGX 500/day
        $this->actingAs($this->librarian)->post('/admin/circulation/return', ['copy_code' => $copy->code])->assertSessionHasNoErrors();

        $this->assertSame('available', $copy->fresh()->status);
        $this->assertSame(1500, Fine::first()->amount);
        $this->assertSame(1500, $member->outstandingFines());
    }

    public function test_fine_is_capped(): void
    {
        LibrarySettings::set(['fine_max_per_loan' => 2000]);
        $member = Member::factory()->create();
        $copy = Copy::factory()->create();
        $this->issue($member, $copy);

        $this->travel(60)->days();
        $this->actingAs($this->librarian)->post('/admin/circulation/return', ['copy_code' => $copy->code]);

        $this->assertSame(2000, Fine::first()->amount);
    }

    public function test_on_time_return_has_no_fine(): void
    {
        $copy = Copy::factory()->create();
        $this->issue(Member::factory()->create(), $copy);
        $this->travel(10)->days();
        $this->actingAs($this->librarian)->post('/admin/circulation/return', ['copy_code' => $copy->code]);

        $this->assertSame(0, Fine::count());
    }

    public function test_reservation_queue_holds_returned_copy_and_notifies(): void
    {
        $copy = Copy::factory()->create();
        $borrower = Member::factory()->create();
        $first = Member::factory()->create();
        $second = Member::factory()->create();
        $this->issue($borrower, $copy);

        $this->actingAs($first->user)->post("/my/reservations/{$copy->book_id}")->assertSessionHasNoErrors();
        $this->travel(1)->minutes();
        $this->actingAs($second->user)->post("/my/reservations/{$copy->book_id}")->assertSessionHasNoErrors();
        $this->assertSame(2, Reservation::where('member_id', $second->id)->first()->position());

        $this->actingAs($this->librarian)->post('/admin/circulation/return', ['copy_code' => $copy->code]);

        $this->assertSame('on_hold', $copy->fresh()->status);
        $this->assertSame('ready', Reservation::where('member_id', $first->id)->first()->status);
        $this->assertTrue(LibraryNotification::where('user_id', $first->user_id)->where('channel', 'sms')->exists());

        // Held copy cannot go to someone else, but can go to the member it is held for.
        $this->issue($second, $copy)->assertSessionHasErrors('copy');
        $this->issue($first, $copy)->assertSessionHasNoErrors();
        $this->assertSame('fulfilled', Reservation::where('member_id', $first->id)->first()->status);
    }

    public function test_cannot_reserve_when_a_copy_is_on_the_shelf(): void
    {
        $copy = Copy::factory()->create();

        $this->actingAs(Member::factory()->create()->user)->post("/my/reservations/{$copy->book_id}")->assertSessionHasErrors('book');
    }

    public function test_uncollected_holds_expire_and_pass_to_next_member(): void
    {
        $copy = Copy::factory()->create();
        $this->issue(Member::factory()->create(), $copy);
        $first = Member::factory()->create();
        $second = Member::factory()->create();
        Reservation::create(['book_id' => $copy->book_id, 'member_id' => $first->id, 'created_at' => now()->subHour()]);
        Reservation::create(['book_id' => $copy->book_id, 'member_id' => $second->id]);

        $this->actingAs($this->librarian)->post('/admin/circulation/return', ['copy_code' => $copy->code]);
        $this->travel(5)->days();
        $this->artisan('library:daily')->assertSuccessful();

        $this->assertSame('expired', Reservation::where('member_id', $first->id)->first()->status);
        $this->assertSame('ready', Reservation::where('member_id', $second->id)->first()->status);
        $this->assertSame('on_hold', $copy->fresh()->status);
    }

    public function test_member_can_renew_unless_reserved_or_limit_reached(): void
    {
        LibrarySettings::set(['max_renewals' => 1]);
        $member = Member::factory()->create();
        $copy = Copy::factory()->create();
        $this->issue($member, $copy);
        $loan = Loan::first();
        $originalDue = $loan->due_at->copy();

        $this->actingAs($member->user)->post("/my/loans/{$loan->id}/renew")->assertSessionHasNoErrors();
        $this->assertTrue($loan->fresh()->due_at->isSameDay($originalDue->addDays(14)));

        $this->actingAs($member->user)->post("/my/loans/{$loan->id}/renew")->assertSessionHasErrors('loan');
    }

    public function test_renewal_blocked_when_someone_is_waiting(): void
    {
        $member = Member::factory()->create();
        $copy = Copy::factory()->create();
        $this->issue($member, $copy);
        Reservation::create(['book_id' => $copy->book_id, 'member_id' => Member::factory()->create()->id]);

        $this->actingAs($member->user)->post('/my/loans/'.Loan::first()->id.'/renew')->assertSessionHasErrors('loan');
    }

    public function test_member_cannot_renew_someone_elses_loan(): void
    {
        $copy = Copy::factory()->create();
        $this->issue(Member::factory()->create(), $copy);

        $this->actingAs(Member::factory()->create()->user)->post('/my/loans/'.Loan::first()->id.'/renew')->assertForbidden();
    }

    public function test_daily_job_sends_reminders_and_overdue_notices_once(): void
    {
        $member = Member::factory()->create();
        $copy = Copy::factory()->create();
        $this->issue($member, $copy);

        $this->travel(13)->days();
        $this->artisan('library:daily');
        $this->assertNotNull(Loan::first()->reminder_sent_at);

        $this->travel(3)->days();
        $this->artisan('library:daily');
        $this->artisan('library:daily');

        $this->assertNotNull(Loan::first()->overdue_notice_sent_at);
        $this->assertSame(2, LibraryNotification::where('channel', 'sms')->count()); // one reminder + one overdue notice
        $this->assertSame(1000, Fine::first()->amount); // fine accrues while overdue
    }

    public function test_new_copy_goes_to_waiting_list(): void
    {
        $book = Book::factory()->create();
        $existing = Copy::factory()->create(['book_id' => $book->id]);
        $this->issue(Member::factory()->create(), $existing);
        $waiting = Member::factory()->create();
        Reservation::create(['book_id' => $book->id, 'member_id' => $waiting->id]);

        $this->actingAs($this->librarian)->post("/admin/books/{$book->id}/copies", ['quantity' => 1, 'condition' => 'new'])->assertSessionHasNoErrors();

        $this->assertSame('ready', Reservation::first()->status);
    }
}

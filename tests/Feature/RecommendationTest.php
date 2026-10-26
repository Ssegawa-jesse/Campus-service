<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookSuggestion;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_recommends_a_book_and_everyone_can_see_it(): void
    {
        $member = Member::factory()->create();

        $this->actingAs($member->user)->post('/recommendations', [
            'title' => 'Clean Code', 'author' => 'Robert C. Martin', 'course' => 'BBC 3102', 'reason' => 'Needed for projects',
        ])->assertRedirect('/recommendations');

        $suggestion = BookSuggestion::first();
        $this->assertSame(1, $suggestion->votes_count);

        auth()->logout();
        $this->get('/recommendations')->assertOk()->assertSee('Clean Code')->assertSee('Needed for projects');
        $this->get('/')->assertOk()->assertSee('Clean Code');
    }

    public function test_duplicate_recommendation_becomes_a_vote(): void
    {
        $this->actingAs(Member::factory()->create()->user)->post('/recommendations', ['title' => 'Clean Code']);
        $this->actingAs(Member::factory()->create()->user)->post('/recommendations', ['title' => 'clean code ']);

        $this->assertSame(1, BookSuggestion::count());
        $this->assertSame(2, BookSuggestion::first()->votes_count);
    }

    public function test_voting_toggles(): void
    {
        $this->actingAs(Member::factory()->create()->user)->post('/recommendations', ['title' => 'Clean Code']);
        $suggestion = BookSuggestion::first();
        $other = Member::factory()->create()->user;

        $this->actingAs($other)->post("/recommendations/{$suggestion->id}/vote");
        $this->assertSame(2, $suggestion->fresh()->votes_count);
        $this->actingAs($other)->post("/recommendations/{$suggestion->id}/vote");
        $this->assertSame(1, $suggestion->fresh()->votes_count);
    }

    public function test_recommend_more_copies_of_unavailable_book(): void
    {
        $book = Book::factory()->create();
        $member = Member::factory()->create();

        $this->actingAs($member->user)->get("/recommendations/create?book={$book->id}")->assertOk()->assertSee($book->title);
        $this->actingAs($member->user)->post('/recommendations', ['title' => $book->title, 'book_id' => $book->id]);

        $this->assertSame($book->id, BookSuggestion::first()->book_id);
        $this->get("/catalogue/{$book->id}")->assertOk()->assertSee('Recommendations about this title');
    }

    public function test_guests_must_log_in_to_recommend_or_vote(): void
    {
        $this->post('/recommendations', ['title' => 'X'])->assertRedirect('/login');
        $this->assertSame(0, BookSuggestion::count());
    }

    public function test_librarian_responds_to_recommendation(): void
    {
        $member = Member::factory()->create();
        $this->actingAs($member->user)->post('/recommendations', ['title' => 'Clean Code']);
        $suggestion = BookSuggestion::first();

        $this->actingAs(User::factory()->librarian()->create())
            ->put("/admin/recommendations/{$suggestion->id}", ['status' => 'ordered', 'response' => '3 copies ordered'])
            ->assertSessionHasNoErrors();

        $this->assertSame('ordered', $suggestion->fresh()->status);
        $this->get('/recommendations')->assertSee('3 copies ordered');
        $this->actingAs($member->user)->put("/admin/recommendations/{$suggestion->id}", ['status' => 'added'])->assertForbidden();
    }

    public function test_only_owner_can_withdraw_pending_recommendation(): void
    {
        $owner = Member::factory()->create()->user;
        $this->actingAs($owner)->post('/recommendations', ['title' => 'Clean Code']);
        $suggestion = BookSuggestion::first();

        $this->actingAs(Member::factory()->create()->user)->delete("/recommendations/{$suggestion->id}")->assertForbidden();
        $this->actingAs($owner)->delete("/recommendations/{$suggestion->id}")->assertRedirect('/recommendations');
        $this->assertSame(0, BookSuggestion::count());
    }
}

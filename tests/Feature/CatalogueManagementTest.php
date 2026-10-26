<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Member;
use App\Models\User;
use App\Support\LibrarySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogueManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_librarian_adds_book_with_copies_and_authors(): void
    {
        $this->actingAs(User::factory()->librarian()->create())->post('/admin/books', [
            'title' => 'Clean Code', 'authors' => 'Robert C. Martin, Someone Else', 'isbn' => '9780132350884',
            'copies' => 3, 'shelf_location' => 'Shelf C9',
        ])->assertSessionHasNoErrors();

        $book = Book::first();
        $this->assertSame(3, $book->copies()->count());
        $this->assertSame('Robert C. Martin, Someone Else', $book->authorNames());

        $this->get('/catalogue?q=Martin')->assertSee('Clean Code');
        $this->get('/catalogue?q=9780132350884')->assertSee('Clean Code');
        $this->get("/catalogue/{$book->id}")->assertSee('Shelf C9')->assertSee('3 of 3 copies on the shelf');
    }

    public function test_librarian_registers_member(): void
    {
        $this->actingAs(User::factory()->librarian()->create())->post('/admin/members', [
            'name' => 'New Student', 'email' => 'new@example.com', 'phone' => '0772000000', 'type' => 'student', 'password' => 'password1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('member', User::where('email', 'new@example.com')->first()->role);
        $this->assertSame(1, Member::count());
    }

    public function test_admin_changes_loan_rules(): void
    {
        $admin = User::factory()->admin()->create();
        $values = LibrarySettings::DEFAULTS;
        $values['fine_per_day'] = 1000;

        $this->actingAs($admin)->put('/admin/settings', $values)->assertSessionHasNoErrors();

        LibrarySettings::flush();
        $this->assertSame(1000, LibrarySettings::int('fine_per_day'));
    }

    public function test_librarian_cannot_manage_staff_or_settings(): void
    {
        $librarian = User::factory()->librarian()->create();

        $this->actingAs($librarian)->get('/admin/staff')->assertForbidden();
        $this->actingAs($librarian)->put('/admin/settings', [])->assertForbidden();
    }
}

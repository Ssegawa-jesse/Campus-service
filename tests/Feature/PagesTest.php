<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\DigitalResource;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Smoke test: every page renders for the roles that should see it. */
class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_pages_render_for_guests(): void
    {
        $book = Book::first();

        foreach (['/', '/forgot-password', '/catalogue', '/catalogue?q=finance', '/catalogue?q=zzzz-nothing', "/catalogue/{$book->id}", '/resources', '/recommendations', '/login', '/register'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_guests_are_sent_to_login_for_private_pages(): void
    {
        foreach (['/dashboard', '/my/loans', '/admin/books', '/recommendations/create'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_member_pages_render(): void
    {
        $user = User::where('email', 'student@mubs.ac.ug')->first();
        $book = Book::first();

        foreach (['/dashboard', '/my/loans', '/my/reservations', '/my/fines', '/my/card', '/profile', '/recommendations/create', "/recommendations/create?book={$book->id}", "/catalogue/{$book->id}"] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_qr_codes_render_without_gd(): void
    {
        $member = User::where('email', 'student@mubs.ac.ug')->first();
        $librarian = User::where('email', 'librarian@mubs.ac.ug')->first();

        $this->actingAs($member)->get('/my/card')->assertOk()->assertSee('<svg', false);
        $this->actingAs($librarian)->get('/admin/copies/labels?book='.Book::first()->id)->assertOk()->assertSee('<svg', false);
    }

    public function test_members_cannot_open_staff_pages(): void
    {
        $user = User::where('email', 'student@mubs.ac.ug')->first();

        $this->actingAs($user)->get('/admin/books')->assertForbidden();
        $this->actingAs($user)->get('/admin/circulation')->assertForbidden();
    }

    public function test_staff_pages_render(): void
    {
        $librarian = User::where('email', 'librarian@mubs.ac.ug')->first();
        $book = Book::first();
        $member = Member::first();

        $urls = [
            '/dashboard', '/admin/books', '/admin/books/create', "/admin/books/{$book->id}/edit", "/admin/books/{$book->id}/copies",
            "/admin/copies/labels?book={$book->id}", '/admin/categories', '/admin/members', '/admin/members/create',
            "/admin/members/{$member->id}", "/admin/members/{$member->id}/edit", "/admin/members/{$member->id}/card",
            '/admin/circulation', '/admin/loans', '/admin/loans?status=overdue', '/admin/loans?status=returned',
            '/admin/reservations', '/admin/fines', '/admin/fines?status=all', '/admin/resources', '/admin/recommendations',
            '/admin/reports', '/admin/reports/print/loans', '/admin/reports/print/overdue', '/admin/reports/print/popular',
            '/admin/reports/print/fines', '/admin/reports/print/members',
        ];

        foreach ($urls as $url) {
            $this->actingAs($librarian)->get($url)->assertOk();
        }

        $this->actingAs($librarian)->get('/admin/reports/export/overdue')->assertOk()->assertHeader('content-type', 'text/csv; charset=utf-8');
        $this->actingAs($librarian)->get('/admin/settings')->assertForbidden();
    }

    public function test_admin_pages_render(): void
    {
        $admin = User::where('email', 'admin@mubs.ac.ug')->first();

        foreach (['/admin/settings', '/admin/staff', '/admin/staff/create', "/admin/staff/{$admin->id}/edit", '/admin/audit-logs', '/admin/notifications'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_circulation_lookup_returns_member_and_copy(): void
    {
        $librarian = User::where('email', 'librarian@mubs.ac.ug')->first();

        $this->actingAs($librarian)->getJson('/admin/circulation/lookup?code=2400720657')->assertOk()->assertJson(['kind' => 'member']);
        $this->actingAs($librarian)->getJson('/admin/circulation/lookup?code=BK-001-01')->assertOk()->assertJson(['kind' => 'copy']);
        $this->actingAs($librarian)->getJson('/admin/circulation/lookup?code=nope')->assertNotFound();
    }

    public function test_resources_download_requires_login(): void
    {
        $resource = DigitalResource::first();

        $this->get("/resources/{$resource->id}/download")->assertRedirect('/login');
        $this->actingAs(User::where('role', 'member')->first())->get("/resources/{$resource->id}/download")->assertOk();
        $this->assertSame(1, $resource->fresh()->downloads);
    }
}

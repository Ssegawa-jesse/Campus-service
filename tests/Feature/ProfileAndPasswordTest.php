<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Member;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileAndPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** A real JPEG, so the tests also run on PHP without the GD extension. */
    protected function photo(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, file_get_contents(base_path('tests/fixtures/photo.jpg')));
    }

    public function test_forgot_password_emails_a_link_that_resets_the_password(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'jane@example.com']);

        $this->post('/forgot-password', ['email' => 'jane@example.com'])->assertSessionHas('success');

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->get("/reset-password/{$token}?email=jane@example.com")->assertOk();
        $this->post('/reset-password', [
            'token' => $token, 'email' => 'jane@example.com', 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass',
        ])->assertRedirect('/login');

        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
        $this->post('/login', ['email' => 'jane@example.com', 'password' => 'brand-new-pass'])->assertRedirect('/dashboard');
    }

    public function test_reset_with_bad_token_fails(): void
    {
        User::factory()->create(['email' => 'jane@example.com', 'password' => 'password']);

        $this->post('/reset-password', [
            'token' => 'nonsense', 'email' => 'jane@example.com', 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass',
        ])->assertSessionHasErrors('email');
    }

    public function test_unknown_email_does_not_reveal_whether_account_exists(): void
    {
        $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertSessionHas('success')->assertSessionHasNoErrors();
    }

    public function test_member_uploads_profile_picture_and_card_details(): void
    {
        $member = Member::factory()->create();
        $user = $member->user;

        $this->actingAs($user)->put('/profile', [
            'name' => 'Jane Student', 'email' => $user->email, 'phone' => '0772123456',
            'programme' => 'Bachelor of Business Computing', 'date_of_birth' => now()->subYears(21)->subMonth()->format('Y-m-d'),
            'year_of_study' => 3, 'photo' => $this->photo('me.jpg'),
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertNotNull($user->photo);
        Storage::disk('public')->assertExists($user->photo);

        $this->actingAs($user)->get('/my/card')->assertOk()
            ->assertSee('Jane Student')->assertSee('Bachelor of Business Computing')
            ->assertSee('Year 3')->assertSee('21 years')->assertSee($user->photoUrl(), false);

        // Replacing the photo deletes the old file; removing it clears it.
        $old = $user->photo;
        $this->actingAs($user)->put('/profile', [
            'name' => 'Jane Student', 'email' => $user->email, 'phone' => '0772123456', 'year_of_study' => 3,
            'photo' => $this->photo('new.jpg'),
        ]);
        Storage::disk('public')->assertMissing($old);
        $this->actingAs($user)->delete('/profile/photo');
        $this->assertNull($user->fresh()->photo);
    }

    public function test_photo_must_be_an_image(): void
    {
        $user = Member::factory()->create()->user;

        $this->actingAs($user)->put('/profile', [
            'name' => 'X', 'email' => $user->email, 'phone' => '0772123456', 'year_of_study' => 1,
            'photo' => UploadedFile::fake()->create('virus.exe', 10),
        ])->assertSessionHasErrors('photo');
    }

    public function test_registration_with_photo(): void
    {
        $this->post('/register', [
            'name' => 'Photo Person', 'email' => 'photo@example.com', 'phone' => '0772123456', 'type' => 'student',
            'programme' => 'BCOM', 'date_of_birth' => '2005-01-01', 'year_of_study' => 1,
            'password' => 'secret123', 'password_confirmation' => 'secret123',
            'photo' => $this->photo('me.jpg'),
        ])->assertRedirect('/dashboard');

        $user = User::where('email', 'photo@example.com')->first();
        Storage::disk('public')->assertExists($user->photo);
        $this->assertSame(1, $user->member->year_of_study);
    }

    public function test_students_must_give_year_of_study_when_registering(): void
    {
        $this->post('/register', [
            'name' => 'No Year', 'email' => 'ny@example.com', 'phone' => '0772123456', 'type' => 'student',
            'programme' => 'BCOM', 'date_of_birth' => '2005-01-01',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ])->assertSessionHasErrors('year_of_study');
    }

    public function test_librarian_fetches_cover_by_isbn(): void
    {
        Http::fake(['covers.openlibrary.org/*' => Http::response(str_repeat('x', 5000), 200, ['Content-Type' => 'image/jpeg'])]);
        $book = Book::factory()->create(['isbn' => '978-0-13-394303-0']);

        $this->actingAs(User::factory()->librarian()->create())->post("/admin/books/{$book->id}/fetch-cover")->assertSessionHas('success');

        $this->assertNotNull($book->fresh()->cover_image);
        Storage::disk('public')->assertExists($book->fresh()->cover_image);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/b/isbn/9780133943030-L.jpg'));
    }

    public function test_missing_online_cover_is_reported(): void
    {
        Http::fake(['covers.openlibrary.org/*' => Http::response('', 404)]);
        $book = Book::factory()->create();

        $this->actingAs(User::factory()->librarian()->create())->post("/admin/books/{$book->id}/fetch-cover")->assertSessionHasErrors('book');
        $this->assertNull($book->fresh()->cover_image);
    }

    public function test_fetch_covers_command(): void
    {
        Http::fake(['covers.openlibrary.org/*' => Http::response(str_repeat('x', 5000), 200, ['Content-Type' => 'image/jpeg'])]);
        Book::factory()->count(2)->create();

        $this->artisan('library:fetch-covers')->expectsOutputToContain('Downloaded 2 of 2 covers.')->assertSuccessful();
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** "419 Page Expired" (stale CSRF token) must send users back to the form, not show a dead-end page. */
class ExpiredSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // The test runner normally skips CSRF checks; turn them back on to simulate an expired form.
        $this->app->forgetInstance(ValidateCsrfToken::class);
        ValidateCsrfToken::except([]);
        $this->app->bind('env', fn () => 'local');
    }

    private function postWithStaleToken(string $uri, array $data, string $from)
    {
        return $this->withHeader('referer', url($from))
            ->withMiddleware()
            ->post($uri, $data + ['_token' => 'stale-token']);
    }

    public function test_expired_registration_form_returns_to_form_with_details_kept(): void
    {
        $response = $this->postWithStaleToken('/register', [
            'role' => 'operator', 'name' => 'Grace', 'email' => 'grace@example.com', 'company_name' => 'Grace Safaris',
            'password' => 'secret1234', 'password_confirmation' => 'secret1234',
        ], '/register');

        $response->assertRedirect(url('/register'));
        $response->assertSessionHas('error', fn ($m) => str_contains($m, 'session expired'));
        $this->assertSame('Grace Safaris', session()->getOldInput('company_name'));
        $this->assertNull(session()->getOldInput('password'));
        $this->assertDatabaseMissing('users', ['email' => 'grace@example.com']);
    }

    public function test_expired_logout_just_goes_home(): void
    {
        $this->postWithStaleToken('/logout', [], '/')->assertRedirect(route('home'));
    }
}

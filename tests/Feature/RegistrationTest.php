<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_register_as_a_member(): void
    {
        $this->post('/register', [
            'name' => 'Jane Student',
            'email' => 'jane@example.com',
            'phone' => '0772123456',
            'type' => 'student',
            'member_no' => '2400799999',
            'programme' => 'BBC',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect('/dashboard');

        $user = User::where('email', 'jane@example.com')->first();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('member', $user->role);
        $this->assertSame('2400799999', $user->member->member_no);
        $this->get('/dashboard')->assertOk()->assertSee('2400799999');
    }

    public function test_library_number_is_generated_when_blank(): void
    {
        $this->post('/register', [
            'name' => 'Staff Person', 'email' => 'staff@example.com', 'phone' => '0701234567', 'type' => 'staff',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ])->assertRedirect('/dashboard');

        $this->assertStringStartsWith('STF', User::where('email', 'staff@example.com')->first()->member->member_no);
    }

    public function test_registration_rejects_duplicates_and_bad_input(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post('/register', [
            'name' => '', 'email' => 'taken@example.com', 'phone' => 'abc', 'type' => 'alien',
            'password' => 'short', 'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['name', 'email', 'phone', 'type', 'password']);
    }

    public function test_login_and_suspended_accounts(): void
    {
        $user = User::factory()->create(['email' => 'a@example.com', 'password' => 'password']);

        $this->post('/login', ['email' => 'a@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'a@example.com', 'password' => 'password'])->assertRedirect('/dashboard');
        $this->post('/logout');

        $user->update(['status' => 'suspended']);
        $this->post('/login', ['email' => 'a@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}

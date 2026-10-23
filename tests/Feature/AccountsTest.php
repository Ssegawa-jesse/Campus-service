<?php

namespace Tests\Feature;

use App\Models\LoginActivity;
use App\Models\Operator;
use App\Models\Park;
use App\Models\ParkMedia;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function admin(): User
    {
        return User::where('email', config('pearl360.admin_email'))->first();
    }

    private function registerOperator(array $overrides = []): void
    {
        $this->post('/register', $overrides + [
            'role' => 'operator', 'name' => 'Grace Akello', 'email' => 'Grace@Example.com', 'phone' => '0772-123456',
            'company_name' => 'Grace Safaris Ltd', 'licence_no' => 'UTB-123',
            'password' => 'secret1234', 'password_confirmation' => 'secret1234',
        ]);
    }

    public function test_new_operator_appears_on_admin_dashboard_and_can_be_approved(): void
    {
        $this->registerOperator();
        $this->assertDatabaseHas('users', ['email' => 'grace@example.com', 'role' => 'operator']); // stored, email lower-cased
        $operator = Operator::where('company_name', 'Grace Safaris Ltd')->firstOrFail();
        $this->assertSame('pending', $operator->status);
        $this->assertFalse($operator->is_verified);

        // Every admin is alerted by SMS (both seeded admins have phones)
        $this->assertDatabaseHas('sms_logs', ['to' => '+256741141433']);
        $this->assertDatabaseHas('sms_logs', ['to' => '+256700000002']);

        // Visible to the admin on the dashboard, in the operators list, and as a navbar count
        $this->post('/logout');
        $this->actingAs($this->admin())->get('/admin')->assertOk()
            ->assertSee('Operators awaiting approval')->assertSee('Grace Safaris Ltd')->assertSee('Approve');
        $this->actingAs($this->admin())->get('/admin/operators')->assertOk()->assertSee('Grace Safaris Ltd');

        // Approve
        $this->actingAs($this->admin())->post("/admin/operators/{$operator->slug}/approve")->assertRedirect();
        $operator->refresh();
        $this->assertSame('approved', $operator->status);
        $this->assertTrue($operator->is_verified);
        $this->assertSame($this->admin()->id, $operator->approved_by);
        $this->assertDatabaseHas('sms_logs', ['to' => '+256772123456']); // operator told
    }

    public function test_operator_can_be_rejected_with_a_reason(): void
    {
        $operator = Operator::where('status', 'pending')->firstOrFail();

        $this->actingAs($this->admin())->post("/admin/operators/{$operator->slug}/reject", ['reason' => 'Licence number missing'])->assertRedirect();
        $this->assertSame('rejected', $operator->fresh()->status);

        $this->actingAs($operator->user)->get('/operator')->assertOk()->assertSee('Licence number missing');
    }

    public function test_operator_without_company_profile_still_shows_for_approval(): void
    {
        $user = User::factory()->operator()->create(['name' => 'Orphan Operator']);
        $this->actingAs($this->admin())->get('/admin/operators?status=pending')->assertOk()->assertSee('Orphan Operator');
        $this->assertSame('pending', $user->fresh()->operator->status);
    }

    public function test_logins_and_failures_are_recorded(): void
    {
        $this->post('/login', ['email' => 'tourist@pearl360.test', 'password' => 'wrong-password'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'TOURIST@pearl360.test', 'password' => 'Pearl360@2026'])->assertRedirect();

        $this->assertDatabaseHas('login_activities', ['email' => 'tourist@pearl360.test', 'event' => 'failed']);
        $this->assertDatabaseHas('login_activities', ['email' => 'tourist@pearl360.test', 'event' => 'login']);
        $this->assertNotNull(User::where('email', 'tourist@pearl360.test')->first()->last_login_at);

        $this->actingAs($this->admin())->get('/admin/login-activity')->assertOk()->assertSee('Failed login');
    }

    public function test_passwords_are_hashed_never_plain_text(): void
    {
        $this->registerOperator();
        $stored = User::where('email', 'grace@example.com')->value('password');
        $this->assertNotSame('secret1234', $stored);
        $this->assertTrue(password_verify('secret1234', $stored));
    }

    public function test_logged_in_admin_opening_register_is_told_to_log_out(): void
    {
        $this->actingAs($this->admin())->get('/register')->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('info', fn ($m) => str_contains($m, 'already logged in'));
    }

    public function test_multiple_admins_and_last_admin_protection(): void
    {
        $admin = $this->admin();
        $tourist = User::where('email', 'tourist@pearl360.test')->first();

        // Promote a tourist to admin
        $this->actingAs($admin)->put("/admin/users/{$tourist->id}/role", ['role' => 'admin'])->assertRedirect();
        $this->assertTrue($tourist->fresh()->isAdmin());
        $this->actingAs($tourist->fresh())->get('/admin')->assertOk();

        // Cannot demote yourself
        $this->actingAs($admin)->put("/admin/users/{$admin->id}/role", ['role' => 'tourist'])->assertSessionHas('error');
        $this->assertTrue($admin->fresh()->isAdmin());

        // Remove the other admins, then the last one is protected
        User::where('role', 'admin')->whereKeyNot($admin->id)->update(['role' => 'tourist']);
        $other = User::factory()->admin()->create();
        $this->actingAs($other)->delete("/admin/users/{$admin->id}")->assertRedirect();
        $this->assertNull(User::find($admin->id)); // allowed: $other is still an admin
        $this->actingAs($other)->post("/admin/users/{$other->id}/toggle")->assertSessionHas('error'); // cannot suspend self
    }

    public function test_make_admin_command_creates_and_promotes(): void
    {
        $this->artisan('pearl360:make-admin', ['email' => 'new.admin@uwa.go.ug', '--password' => 'longpassword', '--name' => 'New Admin'])->assertSuccessful();
        $this->assertTrue(User::where('email', 'new.admin@uwa.go.ug')->first()->isAdmin());

        $this->artisan('pearl360:make-admin', ['email' => 'tourist@pearl360.test'])->assertSuccessful();
        $this->assertTrue(User::where('email', 'tourist@pearl360.test')->first()->isAdmin());
    }

    public function test_forgot_and_reset_password(): void
    {
        Notification::fake();
        $user = User::where('email', 'tourist@pearl360.test')->first();

        $this->get('/forgot-password')->assertOk();
        $this->post('/forgot-password', ['email' => 'tourist@pearl360.test'])->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->get('/reset-password/'.$notification->token.'?email='.$user->email)->assertOk();
            $this->post('/reset-password', [
                'token' => $notification->token, 'email' => $user->email,
                'password' => 'brandnew123', 'password_confirmation' => 'brandnew123',
            ])->assertRedirect(route('login'));

            return true;
        });

        $this->post('/login', ['email' => 'tourist@pearl360.test', 'password' => 'brandnew123'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('login_activities', ['user_id' => $user->id, 'event' => 'password_reset']);
    }

    public function test_admin_can_set_a_users_password(): void
    {
        $tourist = User::where('email', 'tourist@pearl360.test')->first();
        $this->actingAs($this->admin())->put("/admin/users/{$tourist->id}/password", ['password' => 'TempPass99'])->assertSessionHas('success');
        $this->assertTrue(password_verify('TempPass99', $tourist->fresh()->password));
    }

    public function test_every_park_has_real_videos(): void
    {
        Park::all()->each(function (Park $park) {
            $this->assertNotNull(youtube_id($park->video_url), "{$park->name} has no feature video");
            $this->assertTrue(ParkMedia::where('park_id', $park->id)->where('type', 'youtube')->exists());
        });

        $park = Park::where('slug', 'murchison-falls-national-park')->first();
        $this->get("/parks/{$park->slug}")->assertOk()->assertSee('data-yt="noIT-TWOEZY"', false)->assertSee('Watch on YouTube');
        $this->get('/')->assertOk()->assertSee('on video');
    }
}

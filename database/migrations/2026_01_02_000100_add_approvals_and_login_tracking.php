<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Operator approval workflow: pending → approved | rejected (is_verified mirrors "approved").
        Schema::table('operators', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->index()->after('is_verified');
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('approved_by');
        });
        DB::table('operators')->where('is_verified', true)->update(['status' => 'approved', 'approved_at' => now()]);

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('sms_opt_in');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });

        // Every registration, login, failed login, logout and password reset is recorded here.
        // Passwords are NEVER stored in plain text – only the bcrypt hash in users.password.
        Schema::create('login_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->nullable()->index();
            $table->string('event', 30)->index(); // registered | login | failed | logout | password_reset | blocked
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_activities');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['last_login_at', 'last_login_ip']));
        Schema::table('operators', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['status', 'approved_at', 'rejection_reason']);
        });
    }
};

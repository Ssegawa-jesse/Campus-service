<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('tourist')->index()->after('password'); // admin | tourist | operator
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('country', 80)->nullable()->after('phone');
            $table->string('avatar')->nullable()->after('country');
            $table->boolean('is_active')->default(true)->after('role');
            $table->boolean('sms_opt_in')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'country', 'avatar', 'is_active', 'sms_opt_in']);
        });
    }
};

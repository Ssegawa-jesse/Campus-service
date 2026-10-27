<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('phone');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('programme');
            $table->unsignedTinyInteger('year_of_study')->nullable()->after('date_of_birth');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['date_of_birth', 'year_of_study']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('photo');
        });
    }
};

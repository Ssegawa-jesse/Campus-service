<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->string('slug')->unique();
            $table->string('licence_no')->nullable();       // UTB / AUTO licence number
            $table->string('contact_phone', 20);
            $table->string('contact_email');
            $table->string('website')->nullable();
            $table->string('address')->nullable();
            $table->text('about')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('is_verified')->default(false); // approved by admin (UWA)
            $table->timestamps();
        });

        Schema::create('operator_park', function (Blueprint $table) {
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('park_id')->constrained()->cascadeOnDelete();
            $table->primary(['operator_id', 'park_id']);
        });

        Schema::create('tour_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('park_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('includes')->nullable();
            $table->unsignedSmallInteger('days')->default(1);
            $table->unsignedInteger('price_ugx');           // price per person in UGX
            $table->unsignedSmallInteger('max_group')->default(8);
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_packages');
        Schema::dropIfExists('operator_park');
        Schema::dropIfExists('operators');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Murchison Falls Smart Trip Planning modules:
 *  - UWA tiered pricing + scheduling rules on activities (Smart Itinerary + Travel Budgeting)
 *  - places (Smart Navigation: gates, Paraa jetty, Baker's Trail, Pakuba airfield, attractions)
 *  - travel_updates (live operational alerts)
 *  - itineraries (saved smart plans)
 *  - assistant_logs (AI Travel Assistant questions/answers)
 *  - email_logs (notification log next to sms_logs)
 *  - HP5 360° studio: activity links on scenes, hotspots and media; rich hotspots
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type', 20); // gate | jetty | trail | airfield | attraction | lodge | town | forest
            $table->string('zone', 10)->default('south'); // south | river | north | route
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->text('description')->nullable();
            $table->string('tip')->nullable();
            $table->string('opening_hours')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->foreignId('place_id')->nullable()->after('park_id')->constrained()->nullOnDelete();
            $table->decimal('price_fnr_usd', 10, 2)->default(0);   // Foreign Non-Resident (USD)
            $table->decimal('price_fr_usd', 10, 2)->default(0);    // Foreign Resident (USD)
            $table->unsignedInteger('price_eac_ugx')->default(0);  // East African Citizen (UGX)
            $table->decimal('group_fee_usd', 10, 2)->default(0);   // per vehicle/group (ranger guide)
            $table->unsignedInteger('group_fee_ugx')->default(0);
            $table->decimal('hours', 4, 1)->default(2);
            $table->json('slots')->nullable();     // dawn | morning | afternoon | evening | night
            $table->json('interests')->nullable(); // interest tags
            $table->json('requires')->nullable();  // only suggested when one of these interests is chosen
            $table->unsignedTinyInteger('priority')->default(5);
            $table->unsignedTinyInteger('min_age')->default(0);
            $table->string('wet_season_note')->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::table('scenes', function (Blueprint $table) {
            $table->foreignId('activity_id')->nullable()->after('park_id')->constrained()->nullOnDelete();
            $table->string('camera')->default('HP5 360 VR')->after('image_path');
            $table->date('captured_on')->nullable();
            $table->boolean('is_published')->default(true);
        });

        Schema::table('hotspots', function (Blueprint $table) {
            $table->foreignId('activity_id')->nullable()->after('target_scene_id')->constrained()->nullOnDelete();
            $table->string('audio_path')->nullable();
            $table->text('note')->nullable(); // researcher field note
        });

        Schema::table('park_media', function (Blueprint $table) {
            $table->foreignId('activity_id')->nullable()->after('park_id')->constrained()->nullOnDelete();
        });

        Schema::table('tour_packages', function (Blueprint $table) {
            $table->unsignedSmallInteger('slots_available')->default(10);
            $table->date('available_from')->nullable();
            $table->date('available_to')->nullable();
            $table->string('video_url')->nullable();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('itinerary_id')->nullable()->after('tour_package_id');
        });

        Schema::create('travel_updates', function (Blueprint $table) {
            $table->id();
            $table->string('category', 20); // ferry | road | gate | weather | conservation | general
            $table->string('title');
            $table->text('body');
            $table->string('severity', 10)->default('info'); // info | warning | critical
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_pinned')->default(false);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('itineraries', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->date('start_date');
            $table->unsignedTinyInteger('days');
            $table->string('visitor_category', 5); // fnr | fr | eac
            $table->unsignedSmallInteger('adults')->default(1);
            $table->unsignedSmallInteger('children')->default(0);
            $table->json('input');
            $table->json('plan');
            $table->unsignedBigInteger('total_ugx')->default(0);
            $table->string('status', 20)->default('draft'); // draft | booked | paid
            $table->timestamps();
        });

        Schema::create('assistant_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('question');
            $table->text('answer');
            $table->string('source', 20)->default('knowledge-base'); // knowledge-base | claude
            $table->string('intent', 40)->nullable();
            $table->timestamps();
        });

        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('to');
            $table->string('subject');
            $table->text('body');
            $table->string('status', 20)->default('logged'); // sent | logged | failed
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('assistant_logs');
        Schema::dropIfExists('itineraries');
        Schema::dropIfExists('travel_updates');
        Schema::table('bookings', fn (Blueprint $t) => $t->dropColumn('itinerary_id'));
        Schema::table('tour_packages', fn (Blueprint $t) => $t->dropColumn(['slots_available', 'available_from', 'available_to', 'video_url']));
        Schema::table('park_media', fn (Blueprint $t) => $t->dropConstrainedForeignId('activity_id'));
        Schema::table('hotspots', function (Blueprint $t) {
            $t->dropConstrainedForeignId('activity_id');
            $t->dropColumn(['audio_path', 'note']);
        });
        Schema::table('scenes', function (Blueprint $t) {
            $t->dropConstrainedForeignId('activity_id');
            $t->dropColumn(['camera', 'captured_on', 'is_published']);
        });
        Schema::table('activities', function (Blueprint $t) {
            $t->dropConstrainedForeignId('place_id');
            $t->dropUnique(['slug']);
            $t->dropColumn(['slug', 'price_fnr_usd', 'price_fr_usd', 'price_eac_ugx', 'group_fee_usd', 'group_fee_ugx', 'hours', 'slots', 'interests', 'requires', 'priority', 'min_age', 'wet_season_note', 'is_active']);
        });
        Schema::dropIfExists('places');
    }
};

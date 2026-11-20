<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type', 30)->default('national_park'); // national_park | wildlife_reserve | sanctuary
            $table->string('region', 60);
            $table->string('tagline')->nullable();
            $table->text('description');
            $table->text('how_to_get_there')->nullable();
            $table->string('best_time')->nullable();                // human readable, e.g. "June–September"
            $table->json('best_months')->nullable();                 // [1,2,6,7,8,9,12]
            $table->json('interests')->nullable();                   // ["gorillas","birding",...]
            $table->unsignedTinyInteger('difficulty_level')->default(2); // 1 easy, 2 moderate, 3 hard
            $table->decimal('min_cost', 10, 2)->default(0);          // USD, indicative entry/permit cost
            $table->unsignedSmallInteger('min_days')->default(1);
            $table->unsignedSmallInteger('area_km2')->nullable();
            $table->unsignedSmallInteger('distance_from_kampala_km')->nullable();
            $table->year('established')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('cover_image')->nullable();
            $table->string('video_url')->nullable();                 // YouTube / Vimeo / uploaded video path
            $table->boolean('is_unesco')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parks');
    }
};

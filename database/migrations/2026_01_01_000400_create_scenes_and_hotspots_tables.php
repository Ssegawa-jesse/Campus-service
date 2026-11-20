<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('park_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('image_path');            // equirectangular 360° image (2:1)
            $table->text('description')->nullable();
            $table->decimal('initial_pitch', 6, 2)->default(0);
            $table->decimal('initial_yaw', 6, 2)->default(0);
            $table->string('audio_path')->nullable(); // optional ambient sound / narration
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
        });

        Schema::create('hotspots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scene_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_scene_id')->nullable()->constrained('scenes')->nullOnDelete();
            $table->string('type', 10)->default('info'); // scene | info
            $table->decimal('pitch', 6, 2);
            $table->decimal('yaw', 6, 2);
            $table->string('title')->nullable();
            $table->text('info_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotspots');
        Schema::dropIfExists('scenes');
    }
};

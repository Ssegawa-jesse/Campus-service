<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('park_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category', 40); // primates, game_drive, boat, hiking, birding, culture, nature
            $table->unsignedTinyInteger('fitness_level')->default(1); // 1 low, 2 medium, 3 high
            $table->string('duration')->nullable();
            $table->decimal('indicative_cost', 10, 2)->default(0); // USD
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};

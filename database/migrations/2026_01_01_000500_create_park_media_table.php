<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Photo & video gallery for each park. Admins upload files or paste YouTube links.
        Schema::create('park_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('park_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10)->default('image'); // image | video | youtube
            $table->string('path');                       // file path under /uploads or a URL
            $table->string('caption')->nullable();
            $table->string('credit')->nullable();         // photographer / source (e.g. "UWA")
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('park_media');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Murchison Falls focus:
 * - each 360° scene can be linked to an activity (config/murchison.php), so the smart itinerary can show "View in 360°";
 * - a booking can carry the tourist's smart itinerary (the encoded plan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scenes', function (Blueprint $table) {
            $table->string('activity_key', 40)->nullable()->after('park_id')->index();
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->text('itinerary')->nullable()->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('scenes', fn (Blueprint $table) => $table->dropColumn('activity_key'));
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn('itinerary'));
    }
};

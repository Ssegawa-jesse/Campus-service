<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the table that stores book reservations.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('student_name');
            $table->string('registration_number', 50)->index();
            $table->string('programme');
            $table->string('book_title');
            $table->date('pickup_date')->index();
            $table->timestamps();
        });
    }

    /**
     * Drop the reservations table.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};

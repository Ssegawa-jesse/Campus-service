<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A booking starts as an enquiry. The operator sends a quote, the tourist pays online,
        // and the operator confirms.  pending → quoted → paid → confirmed → completed (or cancelled / declined)
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('tourist_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('park_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tour_package_id')->nullable()->constrained()->nullOnDelete();
            $table->date('travel_date');
            $table->unsignedSmallInteger('group_size')->default(1);
            $table->string('contact_phone', 20);
            $table->text('message')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedInteger('quoted_amount')->nullable(); // UGX total
            $table->text('operator_response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tx_ref')->unique();                 // our reference sent to the gateway
            $table->string('gateway', 30);                      // simulate | flutterwave
            $table->string('method', 30)->nullable();           // mobile_money_mtn | mobile_money_airtel | card
            $table->string('phone', 20)->nullable();
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('UGX');
            $table->string('status', 20)->default('pending')->index(); // pending | successful | failed
            $table->string('gateway_reference')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('bookings');
    }
};

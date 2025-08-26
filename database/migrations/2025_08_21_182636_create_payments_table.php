<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('transaction_id')->unique()->nullable(); // Flutterwave transaction ID
            $table->string('reference')->unique(); // Our internal reference
            $table->decimal('amount', 10, 2); // Amount paid
            $table->string('currency', 3)->default('NGN');
            $table->enum('payment_method', ['card', '', 'flutterwave', 'bank_transfer', 'mobile_money'])->default('card');
            $table->enum('status', ['pending', 'processing', 'successful', 'failed', 'cancelled', 'refunded'])->default('pending');
            $table->string('gateway_response')->nullable(); // Response from payment gateway
            $table->json('gateway_data')->nullable(); // Full response data from gateway
            $table->string('card_last4')->nullable(); // Last 4 digits of card
            $table->string('card_brand')->nullable(); // Visa, Mastercard, etc.
            $table->string('card_holder_name')->nullable();
            $table->string('payment_channel')->nullable(); // card, ussd, banktransfer, etc.
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('paid_at')->nullable(); // When payment was successful
            $table->timestamp('failed_at')->nullable(); // When payment failed
            $table->text('failure_reason')->nullable(); // Reason for failure
            $table->json('meta_data')->nullable(); // Additional metadata
            $table->timestamps();

            // Indexes for better performance
            $table->index(['user_id', 'status']);
            $table->index(['order_id', 'status']);
            $table->index(['transaction_id']);
            $table->index(['reference']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

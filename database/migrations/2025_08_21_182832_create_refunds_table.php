<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('reference')->unique(); // Our internal reference
            $table->string('transaction_id')->nullable(); // Gateway refund transaction ID
            $table->decimal('amount', 10, 2); // Refund amount
            $table->string('currency', 3)->default('NGN');
            $table->enum('status', ['pending', 'processing', 'successful', 'failed', 'cancelled'])->default('pending');
            $table->text('reason'); // Reason for refund
            $table->string('gateway_response')->nullable(); // Response from payment gateway
            $table->json('gateway_data')->nullable(); // Full response data from gateway
            $table->string('processed_by')->nullable(); // Admin who processed the refund
            $table->timestamp('processed_at')->nullable(); // When refund was processed
            $table->timestamp('failed_at')->nullable(); // When refund failed
            $table->text('failure_reason')->nullable(); // Reason for failure
            $table->json('meta_data')->nullable(); // Additional metadata
            $table->timestamps();

            // Indexes for better performance
            $table->index(['user_id', 'status']);
            $table->index(['payment_id', 'status']);
            $table->index(['order_id', 'status']);
            $table->index(['reference']);
            $table->index(['transaction_id']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};

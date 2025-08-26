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
        Schema::create('pending_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('payment_reference')->unique();
            $table->json('order_data');
            $table->enum('status', ['pending_payment', 'payment_verified', 'order_created', 'expired', 'failed'])->default('pending_payment');
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_method')->default('card');
            $table->timestamp('expires_at');
            $table->timestamp('payment_verified_at')->nullable();
            $table->timestamp('order_created_at')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for better performance
            $table->index(['user_id', 'status']);
            $table->index('payment_reference');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_orders');
    }
};

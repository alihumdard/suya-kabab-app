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
        Schema::create('card_payloads', function (Blueprint $table) {
            $table->id();
            $table->string('card_number', 16); // Card number (masked/encrypted in production)
            $table->string('expiry_month', 2); // Expiry month (01-12)
            $table->string('expiry_year', 2); // Expiry year (last 2 digits)
            $table->string('cvv', 4)->nullable(); // CVV (should be encrypted, optional for storage)
            $table->string('card_holder_name'); // Cardholder name
            $table->string('email'); // Email associated with the payment
            $table->string('currency', 3)->default('NGN'); // Currency code (ISO 4217)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Foreign key to users table
            $table->timestamps();
            
            // Add indexes for better performance
            $table->index(['user_id', 'created_at']);
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_payloads');
    }
};

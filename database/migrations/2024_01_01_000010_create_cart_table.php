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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->json('customizations')->nullable(); // Store selected add-ons
            $table->text('special_instructions')->nullable(); // Store special notes
            $table->decimal('addon_total', 10, 2)->default(0); // Total price of add-ons
            $table->timestamps();

            // Note: No unique constraint on user_id and product_id
            // because users can have multiple entries for the same product with different customizations
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
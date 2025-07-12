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
        Schema::create('product_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('addon_category_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "Tomato", "Regular Fries", "Coca Cola"
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2); // Price for this add-on
            $table->string('image')->nullable(); // Image for the add-on
            $table->string('sku', 100)->unique()->nullable(); // SKU for inventory
            $table->boolean('track_quantity')->default(false); // Whether to track stock
            $table->integer('quantity')->default(0); // Stock quantity
            $table->integer('sort_order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_addons');
    }
};
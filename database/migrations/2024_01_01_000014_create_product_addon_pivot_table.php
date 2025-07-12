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
        Schema::create('product_addon_pivot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_addon_id')->constrained()->onDelete('cascade');
            $table->boolean('is_required')->default(false); // Whether this add-on is required
            $table->integer('min_quantity')->default(0); // Minimum quantity user can select
            $table->integer('max_quantity')->default(10); // Maximum quantity user can select
            $table->integer('sort_order')->default(0); // Order in which add-ons appear
            $table->timestamps();

            // Ensure unique combination of product and add-on
            $table->unique(['product_id', 'product_addon_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_addon_pivot');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\After;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_addons', function (Blueprint $table) {
            $table->foreignId('addon_category_id')->after('slug')->constrained('addon_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_addons', function (Blueprint $table) {
            $table->dropForeign(['addon_category_id']);
            $table->dropColumn('addon_category_id');
        });
    }
};
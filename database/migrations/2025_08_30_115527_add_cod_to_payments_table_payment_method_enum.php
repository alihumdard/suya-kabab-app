<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, add 'cod' to the ENUM temporarily alongside existing values (including empty string)
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('card', '', 'cod', 'flutterwave', 'bank_transfer', 'mobile_money') DEFAULT 'card'");
        
        // Then update any existing empty string values to 'cod'
        DB::statement("UPDATE payments SET payment_method = 'cod' WHERE payment_method = ''");
        
        // Finally, remove empty string from the ENUM
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('card', 'cod', 'flutterwave', 'bank_transfer', 'mobile_money') DEFAULT 'card'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, add empty string back to the ENUM temporarily
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('card', '', 'cod', 'flutterwave', 'bank_transfer', 'mobile_money') DEFAULT 'card'");
        
        // Then update any 'cod' values back to empty string
        DB::statement("UPDATE payments SET payment_method = '' WHERE payment_method = 'cod'");
        
        // Finally, remove 'cod' from the ENUM (back to original state)
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('card', '', 'flutterwave', 'bank_transfer', 'mobile_money') DEFAULT 'card'");
    }
};

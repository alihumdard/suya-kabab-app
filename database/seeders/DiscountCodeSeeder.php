<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DiscountCode;
use Carbon\Carbon;

class DiscountCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $discountCodes = [
            [
                'code' => 'WELCOME10',
                'type' => 'percentage',
                'value' => 10.00,
                'minimum_amount' => 100.00,
                'maximum_discount' => 50.00,
                'usage_limit' => 100,
                'used_count' => 0,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMonths(3),
                'is_active' => true,
            ],
            [
                'code' => 'SAVE50',
                'type' => 'fixed',
                'value' => 50.00,
                'minimum_amount' => 200.00,
                'maximum_discount' => null,
                'usage_limit' => 50,
                'used_count' => 0,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMonths(2),
                'is_active' => true,
            ],
            [
                'code' => 'NEWUSER',
                'type' => 'percentage',
                'value' => 15.00,
                'minimum_amount' => 0.00,
                'maximum_discount' => 100.00,
                'usage_limit' => 500,
                'used_count' => 0,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMonths(6),
                'is_active' => true,
            ],
            [
                'code' => 'EXPIRED',
                'type' => 'fixed',
                'value' => 25.00,
                'minimum_amount' => 100.00,
                'maximum_discount' => null,
                'usage_limit' => 10,
                'used_count' => 0,
                'starts_at' => Carbon::now()->subMonth(),
                'expires_at' => Carbon::now()->subDays(7),
                'is_active' => false,
            ],
        ];

        foreach ($discountCodes as $code) {
            DiscountCode::create($code);
        }
    }
}
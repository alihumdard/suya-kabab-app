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
                'expires_at' => Carbon::now()->addYear(),
                'is_active' => true,
            ],
            [
                'code' => 'SUMMER25',
                'type' => 'percentage',
                'value' => 25.00,
                'minimum_amount' => 150.00,
                'maximum_discount' => 200.00,
                'usage_limit' => 200,
                'used_count' => 0,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMonths(6),
                'is_active' => true,
            ],
            [
                'code' => 'FREESHIP',
                'type' => 'percentage',
                'value' => 50.00,
                'minimum_amount' => 300.00,
                'maximum_discount' => null,
                'usage_limit' => 150,
                'used_count' => 0,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMonths(4),
                'is_active' => true,
            ],
            [
                'code' => 'BULK15',
                'type' => 'percentage',
                'value' => 15.00,
                'minimum_amount' => 500.00,
                'maximum_discount' => 300.00,
                'usage_limit' => 75,
                'used_count' => 0,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMonths(5),
                'is_active' => true,
            ],
            [
                'code' => 'WEEKEND20',
                'type' => 'percentage',
                'value' => 20.00,
                'minimum_amount' => 100.00,
                'maximum_discount' => 100.00,
                'usage_limit' => 300,
                'used_count' => 0,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMonths(2),
                'is_active' => true,
            ],
            [
                'code' => 'FIRSTORDER',
                'type' => 'fixed',
                'value' => 100.00,
                'minimum_amount' => 400.00,
                'maximum_discount' => 100.00,
                'usage_limit' => 1000,
                'used_count' => 0,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addYear(),
                'is_active' => true,
            ],
            [
                'code' => 'MEMBER10',
                'type' => 'percentage',
                'value' => 10.00,
                'minimum_amount' => 0.00,
                'maximum_discount' => 50.00,
                'usage_limit' => null,
                'used_count' => 0,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addYear(),
                'is_active' => true,
            ],
            [
                'code' => 'FLAT100',
                'type' => 'fixed',
                'value' => 100.00,
                'minimum_amount' => 200.00,
                'maximum_discount' => 100.00,
                'usage_limit' => 250,
                'used_count' => 0,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMonths(3),
                'is_active' => true,
            ],
            [
                'code' => 'SPECIAL30',
                'type' => 'percentage',
                'value' => 30.00,
                'minimum_amount' => 300.00,
                'maximum_discount' => 250.00,
                'usage_limit' => 50,
                'used_count' => 0,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMonths(2),
                'is_active' => true,
            ],
            [
                'code' => 'HAPPY25',
                'type' => 'percentage',
                'value' => 25.00,
                'minimum_amount' => 150.00,
                'maximum_discount' => 150.00,
                'usage_limit' => 100,
                'used_count' => 0,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMonths(4),
                'is_active' => true,
            ],
            [
                'code' => 'MEGA200',
                'type' => 'fixed',
                'value' => 200.00,
                'minimum_amount' => 1000.00,
                'maximum_discount' => 200.00,
                'usage_limit' => 30,
                'used_count' => 0,
                'starts_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMonths(3),
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
            DiscountCode::updateOrCreate(
                ['code' => $code['code']],
                $code
            );
        }
    }
}
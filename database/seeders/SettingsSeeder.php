<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'delivery_charges',
                'value' => '100',
                'type' => 'decimal',
                'description' => 'Standard delivery charges for orders'
            ],
            [
                'key' => 'free_delivery_minimum',
                'value' => '500',
                'type' => 'decimal',
                'description' => 'Minimum order amount for free delivery'
            ],
            [
                'key' => 'tax_rate',
                'value' => '0',
                'type' => 'decimal',
                'description' => 'Tax rate percentage (0-100)'
            ],
            [
                'key' => 'app_name',
                'value' => 'Suya Kabab',
                'type' => 'string',
                'description' => 'Application name'
            ],
            [
                'key' => 'app_phone',
                'value' => '+92-300-1234567',
                'type' => 'string',
                'description' => 'Application contact phone number'
            ],
            [
                'key' => 'app_email',
                'value' => 'info@suyakabab.com',
                'type' => 'string',
                'description' => 'Application contact email'
            ],
            [
                'key' => 'min_order_amount',
                'value' => '200',
                'type' => 'decimal',
                'description' => 'Minimum order amount required'
            ],
            [
                'key' => 'max_delivery_distance',
                'value' => '10',
                'type' => 'decimal',
                'description' => 'Maximum delivery distance in KM'
            ],
            [
                'key' => 'delivery_time_estimate',
                'value' => '30-45',
                'type' => 'string',
                'description' => 'Estimated delivery time in minutes'
            ],
            [
                'key' => 'pickup_time_estimate',
                'value' => '15-20',
                'type' => 'string',
                'description' => 'Estimated pickup time in minutes'
            ]
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
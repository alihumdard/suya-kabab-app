<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Promotion;
use App\Models\Image;
use Carbon\Carbon;

class PromotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $promotions = [
            [
                'title' => 'Summer Sizzle Sale',
                'description' => 'Beat the heat with our hottest deals! Get 20% off on all kabab platters and enjoy free delivery on orders above Rs. 500. Perfect for summer BBQ parties and family gatherings.',
                'status' => 'active',
                'button_text' => 'Order Now',
                'button_url' => 'https://app.suyakabab.com/menu',
                'sort_order' => 1,
                'image_name' => 'summer_sale.jpg'
            ],
            [
                'title' => 'Ramadan Special',
                'description' => 'Iftar made easy with our special Ramadan deals! Pre-order your favorite kababs and get 15% off on combo meals. Perfect for breaking your fast with family.',
                'status' => 'active',
                'button_text' => 'Pre-Order',
                'button_url' => 'https://app.suyakabab.com/ramadan',
                'sort_order' => 2,
                'image_name' => 'ramadan_special.jpg'
            ],
            [
                'title' => 'Eid Celebration Deal',
                'description' => 'Celebrate Eid with our premium kabab selection! This special offer included free appetizers with every large order and complimentary dessert.',
                'status' => 'expired',
                'button_text' => 'View Menu',
                'button_url' => 'https://app.suyakabab.com/menu',
                'sort_order' => 3,
                'image_name' => 'eid_celebration.jpg'
            ],
            [
                'title' => 'Winter Warmth Bundle',
                'description' => 'Stay warm with our hearty winter kabab bundles! Hot, spicy, and perfect for cold nights. Get 25% off on all grilled items and hot beverages.',
                'status' => 'inactive',
                'button_text' => 'Coming Soon',
                'button_url' => 'https://app.suyakabab.com/winter',
                'sort_order' => 4,
                'image_name' => 'winter_bundle.jpg'
            ]
        ];

        foreach ($promotions as $promotionData) {
            // Extract image name and remove it from promotion data
            $imageName = $promotionData['image_name'];
            unset($promotionData['image_name']);

            // Create the promotion
            $promotion = Promotion::create($promotionData);

            // Create a sample image record for the promotion
            // In a real scenario, you would have actual image files
            Image::create([
                'imageable_type' => Promotion::class,
                'imageable_id' => $promotion->id,
                'image_path' => 'promotions/' . $imageName,
                'alt_text' => $promotion->title,
                'mime_type' => 'image/jpeg',
                'size' => rand(50000, 200000), // Random size between 50KB - 200KB
                'dimensions' => json_encode(['width' => 800, 'height' => 600]),
                'is_active' => true,
            ]);
        }
    }
}

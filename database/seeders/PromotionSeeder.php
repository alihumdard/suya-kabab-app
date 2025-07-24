<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Promotion;
use App\Models\Image;

class PromotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clean up all existing promotions and their images to avoid duplicates
        $existingPromotions = Promotion::all();
        foreach ($existingPromotions as $promotion) {
            $promotion->images()->delete();
        }
        Promotion::query()->delete();

        $promotions = [
            [
                'title' => 'Summer Sizzle Sale',
                'description' => 'Beat the heat with our hottest deals! Get 20% off on all kabab platters and enjoy free delivery on orders above Rs. 500. Perfect for summer BBQ parties and family gatherings.',
                'status' => 'active',
                'button_text' => 'Order Now',
                'button_url' => 'https://app.suyakabab.com/menu',
                'sort_order' => 1,
                'image_name' => 'pro3.jpg'
            ],
            [
                'title' => 'Ramadan Special',
                'description' => 'Iftar made easy with our special Ramadan deals! Pre-order your favorite kababs and get 15% off on combo meals. Perfect for breaking your fast with family.',
                'status' => 'active',
                'button_text' => 'Pre-Order',
                'button_url' => 'https://app.suyakabab.com/ramadan',
                'sort_order' => 2,
                'image_name' => 'pro2.jpg'
            ],
            [
                'title' => 'Eid Celebration Deal',
                'description' => 'Celebrate Eid with our premium kabab selection! This special offer included free appetizers with every large order and complimentary dessert.',
                'status' => 'active',
                'button_text' => 'View Menu',
                'button_url' => 'https://app.suyakabab.com/menu',
                'sort_order' => 3,
                'image_name' => 'pro1.jpg'
            ],
        ];

        foreach ($promotions as $promotionData) {
            // Extract image name and remove it from promotion data
            $imageName = $promotionData['image_name'];
            unset($promotionData['image_name']);

            // Create the promotion
            $promotion = Promotion::create($promotionData);

            // Create image record for the promotion
            Image::create([
                'imageable_type' => Promotion::class,
                'imageable_id' => $promotion->id,
                'image_path' => 'images/promotions/' . $imageName,
                'alt_text' => $promotion->title,
                'mime_type' => 'image/jpeg',
                'size' => rand(50000, 200000), // Random size between 50KB - 200KB
                'dimensions' => json_encode(['width' => 800, 'height' => 600]),
                'is_active' => true,
            ]);
        }
    }
}

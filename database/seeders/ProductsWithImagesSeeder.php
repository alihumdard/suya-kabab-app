<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Image;
use Illuminate\Database\Seeder;

class ProductsWithImagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing products and images
        Image::where('imageable_type', Product::class)->delete();
        Product::query()->delete();

        // Create Sample Products with Images
        $products = [


            [
                'category_id' => 2, // Kabab
                'name' => 'Lamb Kabab',
                'slug' => 'lamb-kabab',
                'description' => 'Succulent lamb kabab grilled with herbs and spices.',
                'short_description' => 'Grilled lamb kabab',
                'price' => 2000.00,

                'quantity' => 30,
                'status' => 'active',
                'featured' => true,
                'images' => ['kabab.png', 'special-kabab-4.png']
            ],
            [
                'category_id' => 2, // Kabab
                'name' => 'Chicken Kabab',
                'slug' => 'chicken-kabab',
                'description' => 'Tender chicken kabab marinated in special spices and grilled.',
                'short_description' => 'Marinated chicken kabab',
                'price' => 1500.00,

                'quantity' => 35,
                'status' => 'active',
                'images' => ['2.png', 'chef.png']
            ],
            [
                'category_id' => 3, // Drinks
                'name' => 'Zobo',
                'slug' => 'zobo',
                'description' => 'Traditional Nigerian drink made from hibiscus leaves with natural flavors.',
                'short_description' => 'Traditional hibiscus drink',
                'price' => 500.00,

                'quantity' => 100,
                'status' => 'active',
                'images' => ['image.png']
            ],
            [
                'category_id' => 3, // Drinks
                'name' => 'Fresh Orange Juice',
                'slug' => 'fresh-orange-juice',
                'description' => 'Freshly squeezed orange juice, no artificial additives.',
                'short_description' => 'Fresh orange juice',
                'price' => 600.00,

                'quantity' => 80,
                'status' => 'active',
                'images' => ['331cecef90a30108873dafef918bd2fc3068baa1.jpg']
            ],


        ];

        foreach ($products as $productData) {
            // Extract images array
            $images = $productData['images'] ?? [];
            unset($productData['images']);

            // Create product
            $product = Product::create($productData);

            // Create images for the product
            foreach ($images as $imageName) {
                Image::create([
                    'imageable_id' => $product->id,
                    'imageable_type' => Product::class,
                    'image_path' => 'assets/images/' . $imageName,
                    'alt_text' => $product->name . ' Image',
                    'mime_type' => $this->getMimeType($imageName),
                    'is_active' => true,
                ]);
            }

            $this->command->info("Created product: {$product->name} with " . count($images) . " images");
        }

        $this->command->info('ProductsWithImagesSeeder completed successfully!');
        $this->command->info('Created 8 Products with Images');
    }

    /**
     * Get MIME type from filename extension
     */
    private function getMimeType($filename)
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($extension) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'image/png'
        };
    }
}

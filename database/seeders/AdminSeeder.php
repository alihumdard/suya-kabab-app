<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\Image;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin
        Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@suyakabab.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create Test User
        User::create([
            'name' => 'Test User',
            'email' => 'user@suyakabab.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'email_verified_at' => now(),
            'country' => 'Nigeria',
        ]);

        // Create Categories
        $categories = [
            [
                'name' => 'Suya',
                'slug' => 'suya',
                'description' => 'Traditional Nigerian grilled meat with spices',
                'status' => 'active',
                'image' => 'special-kabab-1.png',
            ],
            [
                'name' => 'Kabab',
                'slug' => 'kabab',
                'description' => 'Delicious grilled kababs with various meats',
                'status' => 'active',
                'image' => 'kabab.png',
            ],
            [
                'name' => 'Drinks',
                'slug' => 'drinks',
                'description' => 'Refreshing beverages and drinks',
                'status' => 'active',
                'image' => '331cecef90a30108873dafef918bd2fc3068baa1.jpg',
            ],
            [
                'name' => 'Sides',
                'slug' => 'sides',
                'description' => 'Side dishes and accompaniments',
                'status' => 'active',
                'image' => 'banner.png',
            ],
        ];

        foreach ($categories as $categoryData) {
            // Extract image name
            $imageName = $categoryData['image'];
            unset($categoryData['image']);

            // Create category
            $category = Category::create($categoryData);

            // Create image for the category
            Image::create([
                'imageable_id' => $category->id,
                'imageable_type' => Category::class,
                'image_path' => 'assets/images/' . $imageName,
                'alt_text' => $category->name . ' Category Image',
                'mime_type' => $this->getMimeType($imageName),
                'is_active' => true,
            ]);
        }

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
                'images' => ['kabab.png']
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
                'images' => ['special-kabab-1.png']
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
            foreach ($images as $index => $imageName) {
                Image::create([
                    'imageable_id' => $product->id,
                    'imageable_type' => Product::class,
                    'image_path' => 'assets/images/' . $imageName,
                    'alt_text' => $product->name . ' Image',
                    'mime_type' => $this->getMimeType($imageName),
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('AdminSeeder completed successfully!');
        $this->command->info('Created: 1 Admin, 1 User, 4 Categories (each with 1 Image), 4 Products (each with 1 Image)');
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
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
        Admin::updateOrCreate(
            ['email' => 'admin@suyakabab.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Create Test User
        User::updateOrCreate(
            ['email' => 'user@suyakabab.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
                'country' => 'Nigeria',
            ]
        );

        // Create Categories
        $categories = [
            [
                'name' => 'Suya',
                'slug' => 'suya',
                'description' => 'Traditional Nigerian grilled meat with spices',
                'status' => 'active',
                'image' => 'cat1.jpg',
            ],
            [
                'name' => 'Kabab',
                'slug' => 'kabab',
                'description' => 'Delicious grilled kababs with various meats',
                'status' => 'active',
                'image' => 'cat2.jpg',
            ],
            [
                'name' => 'Drinks',
                'slug' => 'drinks',
                'description' => 'Refreshing beverages and drinks',
                'status' => 'active',
                'image' => 'cat3.jpg',
            ],
            [
                'name' => 'Sides',
                'slug' => 'sides',
                'description' => 'Side dishes and accompaniments',
                'status' => 'active',
                'image' => 'cat4.jpg',
            ],
        ];

        foreach ($categories as $categoryData) {
            // Extract image name
            $imageName = $categoryData['image'];
            unset($categoryData['image']);

            // Create or update category
            $category = Category::updateOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );

            // Delete existing images for this category to avoid duplicates
            $category->images()->delete();

            // Create image for the category
            Image::create([
                'imageable_id' => $category->id,
                'imageable_type' => Category::class,
                'image_path' => 'images/categories/' . $imageName,
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
                'images' => ['p4.jpg']
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
                'images' => ['p3.jpg']
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
                'images' => ['p2.jpg']
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
                'images' => ['p1.jpg']
            ],


        ];

        // First, create/update all products and store references
        $createdProducts = [];
        foreach ($products as $productData) {
            $images = $productData['images'] ?? [];
            unset($productData['images']);
            $product = Product::updateOrCreate(
                ['slug' => $productData['slug']],
                $productData
            );
            $createdProducts[] = ['product' => $product, 'images' => $images];
        }

        // Then, assign images
        foreach ($createdProducts as $entry) {
            $product = $entry['product'];
            $images = $entry['images'];
            $product->refresh(); // Ensure the model is up-to-date from the DB
            $product->images()->delete();
            foreach ($images as $imageName) {
                Image::create([
                    'imageable_id' => $product->id,
                    'imageable_type' => Product::class,
                    'image_path' => 'images/products/' . $imageName,
                    'alt_text' => $product->name . ' Image',
                    'mime_type' => $this->getMimeType($imageName),
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('AdminSeeder completed successfully!');
        $this->command->info('Created/Updated: 1 Admin, 1 User, 4 Categories (each with 1 Image), 4 Products (each with 1 Image)');
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
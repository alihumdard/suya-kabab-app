<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
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
                'sort_order' => 1,
            ],
            [
                'name' => 'Kabab',
                'slug' => 'kabab',
                'description' => 'Delicious grilled kababs with various meats',
                'status' => 'active',
                'sort_order' => 2,
            ],
            [
                'name' => 'Drinks',
                'slug' => 'drinks',
                'description' => 'Refreshing beverages and drinks',
                'status' => 'active',
                'sort_order' => 3,
            ],
            [
                'name' => 'Sides',
                'slug' => 'sides',
                'description' => 'Side dishes and accompaniments',
                'status' => 'active',
                'sort_order' => 4,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // Create Sample Products
        $products = [
            [
                'category_id' => 1, // Suya
                'name' => 'Beef Suya',
                'slug' => 'beef-suya',
                'description' => 'Tender beef grilled with traditional suya spices and served with onions and tomatoes.',
                'short_description' => 'Traditional beef suya with spices',
                'price' => 1500.00,
                'compare_price' => 2000.00,
                'sku' => 'SUY-BEEF-001',
                'quantity' => 50,
                'status' => 'active',
                'featured' => true,
            ],
            [
                'category_id' => 1, // Suya
                'name' => 'Chicken Suya',
                'slug' => 'chicken-suya',
                'description' => 'Juicy chicken pieces grilled to perfection with aromatic suya spices.',
                'short_description' => 'Grilled chicken with suya spices',
                'price' => 1200.00,
                'sku' => 'SUY-CHCK-001',
                'quantity' => 40,
                'status' => 'active',
                'featured' => true,
            ],
            [
                'category_id' => 2, // Kabab
                'name' => 'Lamb Kabab',
                'slug' => 'lamb-kabab',
                'description' => 'Succulent lamb kabab grilled with herbs and spices.',
                'short_description' => 'Grilled lamb kabab',
                'price' => 2000.00,
                'sku' => 'KAB-LAMB-001',
                'quantity' => 30,
                'status' => 'active',
                'featured' => true,
            ],
            [
                'category_id' => 2, // Kabab
                'name' => 'Chicken Kabab',
                'slug' => 'chicken-kabab',
                'description' => 'Tender chicken kabab marinated in special spices and grilled.',
                'short_description' => 'Marinated chicken kabab',
                'price' => 1500.00,
                'sku' => 'KAB-CHCK-001',
                'quantity' => 35,
                'status' => 'active',
            ],
            [
                'category_id' => 3, // Drinks
                'name' => 'Zobo',
                'slug' => 'zobo',
                'description' => 'Traditional Nigerian drink made from hibiscus leaves with natural flavors.',
                'short_description' => 'Traditional hibiscus drink',
                'price' => 500.00,
                'sku' => 'DRK-ZOBO-001',
                'quantity' => 100,
                'status' => 'active',
            ],
            [
                'category_id' => 3, // Drinks
                'name' => 'Fresh Orange Juice',
                'slug' => 'fresh-orange-juice',
                'description' => 'Freshly squeezed orange juice, no artificial additives.',
                'short_description' => 'Fresh orange juice',
                'price' => 600.00,
                'sku' => 'DRK-OJ-001',
                'quantity' => 80,
                'status' => 'active',
            ],
            [
                'category_id' => 4, // Sides
                'name' => 'Fried Yam',
                'slug' => 'fried-yam',
                'description' => 'Crispy fried yam slices, perfect as a side dish.',
                'short_description' => 'Crispy fried yam',
                'price' => 800.00,
                'sku' => 'SID-YAM-001',
                'quantity' => 60,
                'status' => 'active',
            ],
            [
                'category_id' => 4, // Sides
                'name' => 'Pepper Sauce',
                'slug' => 'pepper-sauce',
                'description' => 'Spicy pepper sauce made with fresh peppers and local spices.',
                'short_description' => 'Spicy pepper sauce',
                'price' => 300.00,
                'sku' => 'SID-PEP-001',
                'quantity' => 200,
                'status' => 'active',
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        $this->command->info('AdminSeeder completed successfully!');
        $this->command->info('Created: 1 Admin, 1 User, 4 Categories, 8 Products');
    }
}
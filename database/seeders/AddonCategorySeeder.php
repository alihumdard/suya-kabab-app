<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AddonCategory;

class AddonCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Toppings',
                'slug' => 'toppings',
                'description' => 'Fresh vegetables and toppings for your kabab',
                'icon' => 'fas fa-leaf',
                'sort_order' => 1,
                'status' => 'active',
            ],
            [
                'name' => 'Fries Section',
                'slug' => 'fries',
                'description' => 'Crispy fries to complement your meal',
                'icon' => 'fas fa-utensils',
                'sort_order' => 2,
                'status' => 'active',
            ],
            [
                'name' => 'Soft Drinks',
                'slug' => 'drinks',
                'description' => 'Refreshing beverages',
                'icon' => 'fas fa-glass-whiskey',
                'sort_order' => 3,
                'status' => 'active',
            ],
            [
                'name' => 'Sauces',
                'slug' => 'sauces',
                'description' => 'Delicious sauces and condiments',
                'icon' => 'fas fa-tint',
                'sort_order' => 4,
                'status' => 'active',
            ],
            [
                'name' => 'Extras',
                'slug' => 'extras',
                'description' => 'Additional items to enhance your meal',
                'icon' => 'fas fa-plus',
                'sort_order' => 5,
                'status' => 'active',
            ],
        ];

        foreach ($categories as $category) {
            AddonCategory::create($category);
        }
    }
}
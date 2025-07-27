<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductAddon;
use App\Models\AddonCategory;

class ProductAddonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch category IDs by slug
        $drinksId = AddonCategory::where('slug', 'drinks')->value('id');
        $friesId = AddonCategory::where('slug', 'fries')->value('id');
        $saucesId = AddonCategory::where('slug', 'sauces')->value('id');
        $extrasId = AddonCategory::where('slug', 'extras')->value('id'); // If you have an 'extras' category, otherwise set to null

        $addons = [
            // Sauce Addons
            [
                'name' => 'Extra Spicy Sauce',
                'slug' => 'extra-spicy-sauce',
                'description' => 'Traditional spicy suya sauce',
                'price' => 200.00,
                'sku' => 'ADDON-SAUCE-001',
                'sort_order' => 1,
                'status' => 'active',
                'addon_category_id' => $saucesId,
            ],
            [
                'name' => 'Mild Sauce',
                'slug' => 'mild-sauce',
                'description' => 'Mild pepper sauce',
                'price' => 200.00,
                'sku' => 'ADDON-SAUCE-002',
                'sort_order' => 2,
                'status' => 'active',
                'addon_category_id' => $saucesId,
            ],

            // Sides
            [
                'name' => 'Regular Fries',
                'slug' => 'regular-fries',
                'description' => 'Crispy fried potatoes',
                'price' => 800.00,
                'sku' => 'ADDON-FRIES-001',
                'track_quantity' => true,
                'quantity' => 50,
                'sort_order' => 3,
                'status' => 'active',
                'addon_category_id' => $friesId,
            ],
            [
                'name' => 'Yam Fries',
                'slug' => 'yam-fries',
                'description' => 'Crispy fried yam slices',
                'price' => 1000.00,
                'sku' => 'ADDON-YAM-001',
                'track_quantity' => true,
                'quantity' => 30,
                'sort_order' => 4,
                'status' => 'active',
                'addon_category_id' => $friesId,
            ],

            // Drinks
            [
                'name' => 'Zobo Drink',
                'slug' => 'zobo-drink',
                'description' => 'Traditional hibiscus drink',
                'price' => 500.00,
                'sku' => 'ADDON-DRINK-001',
                'track_quantity' => true,
                'quantity' => 100,
                'sort_order' => 5,
                'status' => 'active',
                'addon_category_id' => $drinksId,
            ],
            [
                'name' => 'Cold Water',
                'slug' => 'cold-water',
                'description' => 'Chilled bottled water',
                'price' => 200.00,
                'sku' => 'ADDON-WATER-001',
                'track_quantity' => true,
                'quantity' => 200,
                'sort_order' => 6,
                'status' => 'active',
                'addon_category_id' => $drinksId,
            ],

            // Extras
            [
                'name' => 'Extra Onions',
                'slug' => 'extra-onions',
                'description' => 'Fresh sliced onions',
                'price' => 150.00,
                'sku' => 'ADDON-ONION-001',
                'sort_order' => 7,
                'status' => 'active',
                'addon_category_id' => $extrasId,
            ],
            [
                'name' => 'Extra Tomatoes',
                'slug' => 'extra-tomatoes',
                'description' => 'Fresh sliced tomatoes',
                'price' => 150.00,
                'sku' => 'ADDON-TOMATO-001',
                'sort_order' => 8,
                'status' => 'active',
                'addon_category_id' => $extrasId,
            ]
        ];

        foreach ($addons as $addonData) {
            ProductAddon::updateOrCreate(
                ['slug' => $addonData['slug']],
                $addonData
            );
        }
    }
}
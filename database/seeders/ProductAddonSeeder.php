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
        // Get categories
        $toppings = AddonCategory::where('slug', 'toppings')->first();
        $fries = AddonCategory::where('slug', 'fries')->first();
        $drinks = AddonCategory::where('slug', 'drinks')->first();
        $sauces = AddonCategory::where('slug', 'sauces')->first();
        $extras = AddonCategory::where('slug', 'extras')->first();

        $addons = [
            // Toppings
            [
                'addon_category_id' => $toppings->id,
                'name' => 'Tomato',
                'slug' => 'tomato',
                'description' => 'Fresh sliced tomatoes',
                'price' => 20.00,
                'sort_order' => 1,
                'status' => 'active',
            ],
            [
                'addon_category_id' => $toppings->id,
                'name' => 'Cucumber',
                'slug' => 'cucumber',
                'description' => 'Fresh cucumber slices',
                'price' => 20.00,
                'sort_order' => 2,
                'status' => 'active',
            ],
            [
                'addon_category_id' => $toppings->id,
                'name' => 'Onions',
                'slug' => 'onions',
                'description' => 'Fresh onion rings',
                'price' => 15.00,
                'sort_order' => 3,
                'status' => 'active',
            ],
            [
                'addon_category_id' => $toppings->id,
                'name' => 'Lettuce',
                'slug' => 'lettuce',
                'description' => 'Fresh crispy lettuce',
                'price' => 15.00,
                'sort_order' => 4,
                'status' => 'active',
            ],
            [
                'addon_category_id' => $toppings->id,
                'name' => 'Pickles',
                'slug' => 'pickles',
                'description' => 'Tangy pickled vegetables',
                'price' => 25.00,
                'sort_order' => 5,
                'status' => 'active',
            ],
            [
                'addon_category_id' => $toppings->id,
                'name' => 'Cabbage',
                'slug' => 'cabbage',
                'description' => 'Fresh shredded cabbage',
                'price' => 15.00,
                'sort_order' => 6,
                'status' => 'active',
            ],

            // Fries Section
            [
                'addon_category_id' => $fries->id,
                'name' => 'Regular Fries',
                'slug' => 'regular-fries',
                'description' => 'Crispy golden french fries',
                'price' => 80.00,
                'sort_order' => 1,
                'status' => 'active',
            ],
            [
                'addon_category_id' => $fries->id,
                'name' => 'Curly Fries',
                'slug' => 'curly-fries',
                'description' => 'Seasoned curly fries',
                'price' => 100.00,
                'sort_order' => 2,
                'status' => 'active',
            ],
            [
                'addon_category_id' => $fries->id,
                'name' => 'Saucy Fries',
                'slug' => 'saucy-fries',
                'description' => 'Fries with special sauce',
                'price' => 120.00,
                'sort_order' => 3,
                'status' => 'active',
            ],
            [
                'addon_category_id' => $fries->id,
                'name' => 'Cheese Fries',
                'slug' => 'cheese-fries',
                'description' => 'Fries topped with melted cheese',
                'price' => 150.00,
                'sort_order' => 4,
                'status' => 'active',
            ],

            // Soft Drinks
            [
                'addon_category_id' => $drinks->id,
                'name' => 'Coca Cola',
                'slug' => 'coca-cola',
                'description' => 'Classic Coca Cola',
                'price' => 100.00,
                'sort_order' => 1,
                'status' => 'active',
            ],
            [
                'addon_category_id' => $drinks->id,
                'name' => 'Pepsi',
                'slug' => 'pepsi',
                'description' => 'Refreshing Pepsi',
                'price' => 100.00,
                'sort_order' => 2,
                'status' => 'active',
            ],
            [
                'addon_category_id' => $drinks->id,
                'name' => 'Sprite',
                'slug' => 'sprite',
                'description' => 'Lemon-lime Sprite',
                'price' => 100.00,
                'sort_order' => 3,
                'status' => 'active',
            ],
            [
                'addon_category_id' => $drinks->id,
                'name' => 'Fanta',
                'slug' => 'fanta',
                'description' => 'Orange flavored Fanta',
                'price' => 100.00,
                'sort_order' => 4,
                'status' => 'active',
            ],
            [
                'addon_category_id' => $drinks->id,
                'name' => 'Water',
                'slug' => 'water',
                'description' => 'Pure drinking water',
                'price' => 50.00,
                'sort_order' => 5,
                'status' => 'active',
            ],

            // Sauces
            [
                'addon_category_id' => $sauces->id,
                'name' => 'Garlic Sauce',
                'slug' => 'garlic-sauce',
                'description' => 'Creamy garlic sauce',
                'price' => 30.00,
                'sort_order' => 1,
                'status' => 'active',
            ],
            [
                'addon_category_id' => $sauces->id,
                'name' => 'Hot Sauce',
                'slug' => 'hot-sauce',
                'description' => 'Spicy hot sauce',
                'price' => 30.00,
                'sort_order' => 2,
                'status' => 'active',
            ],
            [
                'addon_category_id' => $sauces->id,
                'name' => 'Yogurt Sauce',
                'slug' => 'yogurt-sauce',
                'description' => 'Cool yogurt sauce',
                'price' => 35.00,
                'sort_order' => 3,
                'status' => 'active',
            ],
            [
                'addon_category_id' => $sauces->id,
                'name' => 'BBQ Sauce',
                'slug' => 'bbq-sauce',
                'description' => 'Smoky BBQ sauce',
                'price' => 35.00,
                'sort_order' => 4,
                'status' => 'active',
            ],
            [
                'addon_category_id' => $sauces->id,
                'name' => 'Tahini Sauce',
                'slug' => 'tahini-sauce',
                'description' => 'Sesame tahini sauce',
                'price' => 40.00,
                'sort_order' => 5,
                'status' => 'active',
            ],

            // Extras
            [
                'addon_category_id' => $extras->id,
                'name' => 'Extra Meat',
                'slug' => 'extra-meat',
                'description' => 'Additional portion of meat',
                'price' => 200.00,
                'sort_order' => 1,
                'status' => 'active',
            ],
            [
                'addon_category_id' => $extras->id,
                'name' => 'Extra Cheese',
                'slug' => 'extra-cheese',
                'description' => 'Additional cheese',
                'price' => 50.00,
                'sort_order' => 2,
                'status' => 'active',
            ],
            [
                'addon_category_id' => $extras->id,
                'name' => 'Extra Bread',
                'slug' => 'extra-bread',
                'description' => 'Additional bread/pita',
                'price' => 30.00,
                'sort_order' => 3,
                'status' => 'active',
            ],
            [
                'addon_category_id' => $extras->id,
                'name' => 'Hummus',
                'slug' => 'hummus',
                'description' => 'Creamy hummus dip',
                'price' => 80.00,
                'sort_order' => 4,
                'status' => 'active',
            ],
            [
                'addon_category_id' => $extras->id,
                'name' => 'Salad',
                'slug' => 'salad',
                'description' => 'Fresh mixed salad',
                'price' => 70.00,
                'sort_order' => 5,
                'status' => 'active',
            ],
        ];

        foreach ($addons as $addon) {
            ProductAddon::create($addon);
        }
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductAddon;
use Illuminate\Support\Facades\DB;

class ProductAddonPivotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all products (assuming they exist)
        $products = Product::all();

        if ($products->isEmpty()) {
            $this->command->info('No products found. Please run product seeder first.');
            return;
        }

        // Get addons by category
        $toppings = ProductAddon::whereHas('category', function ($query) {
            $query->where('slug', 'toppings');
        })->get();

        $fries = ProductAddon::whereHas('category', function ($query) {
            $query->where('slug', 'fries');
        })->get();

        $drinks = ProductAddon::whereHas('category', function ($query) {
            $query->where('slug', 'drinks');
        })->get();

        $sauces = ProductAddon::whereHas('category', function ($query) {
            $query->where('slug', 'sauces');
        })->get();

        $extras = ProductAddon::whereHas('category', function ($query) {
            $query->where('slug', 'extras');
        })->get();

        // Link addons to products
        foreach ($products as $product) {
            $pivotData = [];

            // Add all toppings to each product
            foreach ($toppings as $index => $topping) {
                $pivotData[] = [
                    'product_id' => $product->id,
                    'product_addon_id' => $topping->id,
                    'is_required' => false,
                    'min_quantity' => 0,
                    'max_quantity' => 5,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Add all fries to each product
            foreach ($fries as $index => $fry) {
                $pivotData[] = [
                    'product_id' => $product->id,
                    'product_addon_id' => $fry->id,
                    'is_required' => false,
                    'min_quantity' => 0,
                    'max_quantity' => 2,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Add all drinks to each product
            foreach ($drinks as $index => $drink) {
                $pivotData[] = [
                    'product_id' => $product->id,
                    'product_addon_id' => $drink->id,
                    'is_required' => false,
                    'min_quantity' => 0,
                    'max_quantity' => 3,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Add all sauces to each product
            foreach ($sauces as $index => $sauce) {
                $pivotData[] = [
                    'product_id' => $product->id,
                    'product_addon_id' => $sauce->id,
                    'is_required' => false,
                    'min_quantity' => 0,
                    'max_quantity' => 3,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Add all extras to each product
            foreach ($extras as $index => $extra) {
                $pivotData[] = [
                    'product_id' => $product->id,
                    'product_addon_id' => $extra->id,
                    'is_required' => false,
                    'min_quantity' => 0,
                    'max_quantity' => 2,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert all pivot data for this product
            DB::table('product_addon_pivot')->insert($pivotData);
        }

        $this->command->info('Product addon relationships seeded successfully!');
    }
}
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

        // Get addons by slug patterns (since we removed categories)
        $sauces = ProductAddon::where('slug', 'like', '%-sauce')->get();
        $fries = ProductAddon::where('slug', 'like', '%-fries')->get();
        $drinks = ProductAddon::whereIn('slug', ['zobo-drink', 'cold-water'])->get();
        $extras = ProductAddon::whereIn('slug', ['extra-onions', 'extra-tomatoes'])->get();

        // Link addons to products
        foreach ($products as $product) {
            // Clear existing pivot data for this product to avoid duplicates
            DB::table('product_addon_pivot')->where('product_id', $product->id)->delete();

            $pivotData = [];

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

            // Add all fries to each product
            foreach ($fries as $index => $fry) {
                $pivotData[] = [
                    'product_id' => $product->id,
                    'product_addon_id' => $fry->id,
                    'is_required' => false,
                    'min_quantity' => 0,
                    'max_quantity' => 2,
                    'sort_order' => $index + 1 + count($sauces),
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
                    'sort_order' => $index + 1 + count($sauces) + count($fries),
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
                    'sort_order' => $index + 1 + count($sauces) + count($fries) + count($drinks),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert all pivot data for this product
            if (!empty($pivotData)) {
                DB::table('product_addon_pivot')->insert($pivotData);
            }
        }

        $this->command->info('Product addon relationships seeded successfully!');
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AddonCategory;

class AddonCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AddonCategory::updateOrCreate([
            'name' => 'Drinks',
            'slug' => 'drinks',
            'description' => 'Soft drinks and beverages',
            'status' => 'active',
        ]);

        AddonCategory::updateOrCreate([
            'name' => 'Fries',
            'slug' => 'fries',
            'description' => 'Different types of fries',
            'status' => 'active',
        ]);

        AddonCategory::updateOrCreate([
            'name' => 'Sauces',
            'slug' => 'sauces',
            'description' => 'Various sauces and dips',
            'status' => 'active',
        ]);

        AddonCategory::updateOrCreate([
            'name' => 'Extras',
            'slug' => 'extras',
            'description' => 'Extra toppings and sides',
            'status' => 'active',
        ]);
    }
}
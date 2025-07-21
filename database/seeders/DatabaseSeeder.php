<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SettingsSeeder::class,
            AdminSeeder::class,  // Create categories and products first
            PromotionSeeder::class,
            ProductAddonSeeder::class,
            ProductAddonPivotSeeder::class,  // Now products exist for pivot seeding
            DiscountCodeSeeder::class,

        ]);

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin Login: admin@suyakabab.com / password');
        $this->command->info('User Login: user@suyakabab.com / password');
        $this->command->info('Added 5 addon categories and 25 product addons');
        $this->command->info('All products now have available addons for customization');
        $this->command->info('Added 4 sample promotions with different statuses and dates');
    }
}

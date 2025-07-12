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
            AdminSeeder::class,
            DiscountCodeSeeder::class,
        ]);

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin Login: admin@suyakabab.com / password');
        $this->command->info('User Login: user@suyakabab.com / password');
    }
}

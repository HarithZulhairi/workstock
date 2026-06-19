<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Categories; 
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Define your categories
        $categories = [
            'Engine Components',
            'Brakes & Suspension',
            'Fluids & Lubricants',
            'Tools & Accessories',
        ];

        // Loop through and create each one
        foreach ($categories as $category) {
            Categories::create([
                'name' => $category,
            ]);
        }

        // Create a default user for testing
        User::create([
            'name' => 'Admin User',
            'email' => 'admin1@gmail.com',
            'password' => bcrypt('password'),
            'position' => 'Administrator',
            'is_active' => true,
            'phone_number' => '01234567890',
            'address' => '123 Main Street, City, Country',
        ]);
    }
}

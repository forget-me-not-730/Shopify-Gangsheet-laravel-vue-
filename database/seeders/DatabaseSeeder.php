<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        \App\Models\User::create([
            'name' => 'Bags Admin',
            'first_name' => 'Bags',
            'last_name' => 'Admin',
            'type' => 'admin',
            'email' => 'sr.fullstack.geek@gmail.com',
            'password' => bcrypt('secret123')
        ]);
    }
}

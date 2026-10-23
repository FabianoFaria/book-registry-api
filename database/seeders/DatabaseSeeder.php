<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user_zero = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password', // hashed automatically by the 'hashed' cast on User
        ]);

        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => 'password', // hashed automatically by the 'hashed' cast on User
        ]);

        Book::factory()->count(5)->for($user)->create();
        Book::factory()->count(3)->for($user_zero)->create();
    }
}

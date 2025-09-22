<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'name'  => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        User::factory()->general()->create([
            'name'  => 'General User',
            'email' => 'user@example.com',
        ]);

        User::factory()->general()->count(8)->create();
    }
}

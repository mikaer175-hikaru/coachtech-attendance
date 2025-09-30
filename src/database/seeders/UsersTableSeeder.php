<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        // 管理者ユーザー
        User::factory()->admin()->create([
            'name'              => 'Admin User',
            'email'             => 'admin@example.com',
            'password'          => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // 一般ユーザー
        User::factory()->general()->create([
            'name'              => 'General User',
            'email'             => 'user@example.com',
            'password'          => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // その他ダミー一般ユーザー（8件）
        User::factory()->general()->count(8)->create([
            'password'          => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
    }
}

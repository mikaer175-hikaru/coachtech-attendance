<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        // 管理者ユーザー（unique を付けて重複回避）
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'            => 'Admin User',
                'password'        => Hash::make('password123'),
                'is_admin'        => true,
                'is_first_login'  => false,
                'email_verified_at' => now(),
            ]
        );

        // 一般ユーザー（unique を付けて重複回避）
        User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name'            => 'General User',
                'password'        => Hash::make('password123'),
                'is_admin'        => false,
                'is_first_login'  => false,
                'email_verified_at' => now(),
            ]
        );

        // その他ダミー一般ユーザー
        User::factory()->count(10)->sequence(fn ($s) => [
            'email' => "dummy{$s->index}@example.com",
        ])->create();
    }
}

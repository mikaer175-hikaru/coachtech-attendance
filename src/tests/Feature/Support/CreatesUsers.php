<?php

namespace Tests\Feature\Support;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

trait CreatesUsers
{
    protected function createUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'name' => '一般 太郎',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ], $overrides));
    }

    protected function createAdmin(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'name' => '管理 次郎',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ], $overrides));
    }
}

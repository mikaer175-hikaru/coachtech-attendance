<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        // 固定の管理者
        User::factory()->admin()->create([
            'name'  => 'Admin User',
            'email' => 'admin@example.com',
            // password は Factory 側の値（password123）
        ]);

        // 固定の一般ユーザー（ログイン確認用）
        User::factory()->general()->create([
            'name'  => 'General User',
            'email' => 'user@example.com',
        ]);

        // その他の一般ユーザーを複数作成
        User::factory()->general()->count(8)->create();
    }
}

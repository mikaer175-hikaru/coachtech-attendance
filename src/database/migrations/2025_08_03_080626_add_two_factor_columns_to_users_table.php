<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')
                    ->nullable()
                    ->after('password');
            }
            if (!Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                $table->text('two_factor_recovery_codes')
                    ->nullable()
                    ->after('two_factor_secret');
            }
            if (!Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')
                    ->nullable()
                    ->after('two_factor_recovery_codes');
            }

            // 初回ログインフラグ
            if (!Schema::hasColumn('users', 'is_first_login')) {
                $table->boolean('is_first_login')
                    ->default(true)
                    ->after('two_factor_confirmed_at');
            }

            // 管理者フラグ（管理者ログイン実装用）
            if (!Schema::hasColumn('users', 'is_admin')) {
                $table->boolean('is_admin')->default(false)->after('is_first_login');
            }
            try { $table->index('is_admin', 'idx_users_is_admin'); } catch (\Throwable $e) {}
            try { $table->index('is_first_login', 'idx_users_is_first_login'); } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $drops = [];

            foreach ([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'is_first_login',
                'is_admin',
            ] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $drops[] = $col;
                }
            }

            try { $table->dropIndex('idx_users_is_admin'); } catch (\Throwable $e) {}
            try { $table->dropIndex('idx_users_is_first_login'); } catch (\Throwable $e) {}

            if (!empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }
};

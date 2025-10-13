<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Carbon 全体のロケール（曜日など）を日本語に
        Carbon::setLocale(config('app.locale', 'ja'));

        // サーバーの時間ロケールも日本語に
        setlocale(LC_TIME, 'ja_JP.UTF-8', 'ja_JP.utf8', 'ja_JP', 'Japanese');
    }
}

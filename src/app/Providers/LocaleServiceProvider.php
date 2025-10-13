<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;

class LocaleServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Carbon を日本語
        Carbon::setLocale(config('app.locale', 'ja'));

        // PHP のロケールも日本語に
        setlocale(LC_TIME, 'ja_JP.UTF-8', 'ja_JP.utf8', 'ja_JP', 'Japanese');
    }
}

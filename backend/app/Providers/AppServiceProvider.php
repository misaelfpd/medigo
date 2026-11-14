<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Teks waktu relatif ("3 hari yang lalu") yang dikirim ke frontend memakai bahasa Indonesia
        \Carbon\Carbon::setLocale('id');
    }
}

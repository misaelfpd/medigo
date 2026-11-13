<?php

namespace App\Providers;

use App\Auth\BackendSessionUserProvider;
use Illuminate\Support\Facades\Auth;
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
        // Data user diambil dari backend (disimpan di session), bukan dari tabel users frontend
        Auth::provider('medigo-backend', fn () => new BackendSessionUserProvider);
    }
}

<?php

namespace App\Providers;

use App\Auth\BackendSessionUserProvider;
use App\Support\AdminPendingCounts;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
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

        // Waktu relatif berbahasa Indonesia ("2 jam yang lalu") sesuai desain
        \Carbon\Carbon::setLocale('id');

        // Badge jumlah laporan baru di sidebar & lonceng notifikasi admin
        View::composer('layouts.admin', function ($view) {
            $view->with('adminPendingCounts', AdminPendingCounts::get());
        });
    }
}

<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    // Mendaftarkan layanan-layanan dari aplikasi
    public function register(): void
    {
        //
    }

    // Menjalankan tugas awal konfigurasi layanan aplikasi (booting)
    public function boot(): void
    {
        // Paksa HTTPS di production (Vercel menggunakan reverse proxy — trustProxies sudah di-set di bootstrap/app.php)
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Nonaktifkan Vite::prefetch di serverless (tidak ada persistensi koneksi)
        if (!$this->app->environment('production')) {
            Vite::prefetch(concurrency: 3);
        }

        Gate::define('admin-only', function ($user) {
            return $user->is_admin;
        });

        Password::defaults(function () {
            // Membutuhkan 8 karakter, campuran huruf besar/kecil, angka, dan simbol
            return Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        // Pembatasan Akses API (Mencegah serangan Brute-force & DDoS)

        // Batas API umum: 60 permintaan per menit per pengguna
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Batas unggah file: 10 per menit per pengguna (mencegah spam data)
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Batas interval notifikasi: 30 per menit (cek notif setiap 2 detik)
        RateLimiter::for('notifications', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // Batas percobaan login: 5 per menit per IP address (mencegah tebak password)
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}

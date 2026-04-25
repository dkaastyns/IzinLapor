<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    // Template utama untuk dimuat pada kunjungan pertama
    protected $rootView = 'app';

    // Menentukan versi aset saat ini
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    // Menentukan properti yang dibagikan secara global
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id'            => $request->user()->id,
                    'name'          => $request->user()->name,
                    'email'         => $request->user()->email,
                    'phone'         => $request->user()->phone,
                    'is_admin'      => $request->user()->is_admin,
                    'last_login_at' => $request->user()->last_login_at?->toISOString(),
                ] : null,
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}

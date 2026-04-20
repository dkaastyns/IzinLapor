<?php

// Entry point Vercel untuk Laravel
// Semua request HTTP diarahkan ke sini oleh vercel.json

define('LARAVEL_START', microtime(true));

// Pastikan autoload composer tersedia
require __DIR__ . '/../vendor/autoload.php';

// Bootstrap aplikasi Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Jalankan HTTP kernel
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
)->send();

$kernel->terminate($request, $response);

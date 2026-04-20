<?php

// Entry point Vercel PHP serverless untuk Laravel
// Semua request diarahkan ke sini via vercel.json

define('LARAVEL_START', microtime(true));

$projectRoot = dirname(__DIR__);

// ============================================================
// FIX KRITIS: Vercel filesystem read-only kecuali /tmp
// Bootstrap cache (packages.php, services.php) perlu DITULIS
// saat Laravel discover package providers — harus di /tmp
// ============================================================

// 1. Setup storage path di /tmp
$_ENV['APP_STORAGE_PATH'] = '/tmp/laravel-storage';
putenv('APP_STORAGE_PATH=/tmp/laravel-storage');

// 2. Setup bootstrap cache path di /tmp (INI YANG MENYEBABKAN "view does not exist")
$_ENV['APP_BOOTSTRAP_PATH'] = '/tmp/laravel-bootstrap';
putenv('APP_BOOTSTRAP_PATH=/tmp/laravel-bootstrap');

// Buat semua direktori yang diperlukan di /tmp
$dirs = [
    '/tmp/laravel-storage',
    '/tmp/laravel-storage/framework',
    '/tmp/laravel-storage/framework/cache',
    '/tmp/laravel-storage/framework/cache/data',
    '/tmp/laravel-storage/framework/sessions',
    '/tmp/laravel-storage/framework/views',
    '/tmp/laravel-storage/logs',
    '/tmp/laravel-storage/app',
    '/tmp/laravel-storage/app/public',
    '/tmp/laravel-bootstrap',
    '/tmp/laravel-bootstrap/cache',  // <-- Kritis: tempat packages.php & services.php
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Pantau maintenance mode
if (file_exists('/tmp/laravel-storage/framework/maintenance.php')) {
    require '/tmp/laravel-storage/framework/maintenance.php';
}

// Register Composer autoloader
require $projectRoot . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once $projectRoot . '/bootstrap/app.php';

// Handle HTTP request
$app->handleRequest(Illuminate\Http\Request::capture());

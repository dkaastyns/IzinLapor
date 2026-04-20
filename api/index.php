<?php

// Entry point Vercel PHP serverless untuk Laravel
// Semua request diarahkan ke sini via vercel.json

define('LARAVEL_START', microtime(true));

// Set path penting agar relatif ke root project (1 level di atas api/)
$projectRoot = dirname(__DIR__);

// Paksa storage path agar bisa ditulis di runtime Vercel (/tmp)
// Vercel hanya mengizinkan write ke /tmp — semua yang lain read-only
if (!is_writable($projectRoot . '/storage')) {
    // Override storage path ke /tmp yang writable di Vercel
    $_ENV['APP_STORAGE_PATH'] = '/tmp/laravel-storage';
    putenv('APP_STORAGE_PATH=/tmp/laravel-storage');
}

// Pastikan direktori /tmp/laravel-storage/* ada
$tmpStorage = '/tmp/laravel-storage';
$dirs = [
    $tmpStorage,
    $tmpStorage . '/framework',
    $tmpStorage . '/framework/cache',
    $tmpStorage . '/framework/cache/data',
    $tmpStorage . '/framework/sessions',
    $tmpStorage . '/framework/views',
    $tmpStorage . '/logs',
    $tmpStorage . '/app',
    $tmpStorage . '/app/public',
];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Pantau maintenance mode
if (file_exists($projectRoot . '/storage/framework/maintenance.php')) {
    require $projectRoot . '/storage/framework/maintenance.php';
}

// Register Composer autoloader
require $projectRoot . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once $projectRoot . '/bootstrap/app.php';

// Handle HTTP request
$app->handleRequest(Illuminate\Http\Request::capture());

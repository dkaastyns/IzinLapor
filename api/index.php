<?php

// Entry point Vercel PHP serverless untuk Laravel
define('LARAVEL_START', microtime(true));

$projectRoot = dirname(__DIR__);

// ============================================================
// OPTIMASI COLD START: Redirect bootstrap/cache & storage ke /tmp
// ============================================================

$_ENV['APP_STORAGE_PATH']   = '/tmp/laravel-storage';
$_ENV['APP_BOOTSTRAP_PATH'] = '/tmp/laravel-bootstrap';
putenv('APP_STORAGE_PATH=/tmp/laravel-storage');
putenv('APP_BOOTSTRAP_PATH=/tmp/laravel-bootstrap');

// Buat semua direktori yang dibutuhkan di /tmp
$dirs = [
    '/tmp/laravel-storage/framework/cache/data',
    '/tmp/laravel-storage/framework/sessions',
    '/tmp/laravel-storage/framework/views',
    '/tmp/laravel-storage/logs',
    '/tmp/laravel-storage/app/public',
    '/tmp/laravel-bootstrap/cache',
];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// OPTIMASI: Copy packages.php yang sudah di-pre-generate ke /tmp
// Ini menghilangkan overhead package discovery pada setiap cold start (~500ms hemat)
$srcPackages = $projectRoot . '/bootstrap/cache/packages.php';
$dstPackages = '/tmp/laravel-bootstrap/cache/packages.php';
if (!file_exists($dstPackages) && file_exists($srcPackages)) {
    copy($srcPackages, $dstPackages);
}

// Pantau maintenance mode
if (file_exists('/tmp/laravel-storage/framework/maintenance.php')) {
    require '/tmp/laravel-storage/framework/maintenance.php';
}

// Register Composer autoloader
require $projectRoot . '/vendor/autoload.php';

// Bootstrap & handle request
$app = require_once $projectRoot . '/bootstrap/app.php';
$app->handleRequest(Illuminate\Http\Request::capture());

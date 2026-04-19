<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ComplaintController;
use Illuminate\Support\Facades\Route;

// Beranda → alihkan ke login
Route::get('/', function () {
    return redirect()->route('login');
});

// Dashboard (User) logika statistik 
Route::get('/dashboard', [ComplaintController::class, 'userDashboard'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// AUTH USER (SEMUA USER)
Route::middleware(['auth'])->group(function () {

    // Profil Pengguna
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Pengaduan (User)
    Route::get('/complaints', [ComplaintController::class, 'index'])->name('complaints.index');
    Route::get('/complaints/create', [ComplaintController::class, 'create'])->name('complaints.create');
    Route::post('/complaints', [ComplaintController::class, 'store'])
        ->middleware('throttle:uploads')    // Batas: 10 unggahan/menit
        ->name('complaints.store');
    Route::get('/complaints/{complaint}', [ComplaintController::class, 'show'])->name('complaints.show');
    Route::post('/complaints/{complaint}/rating', [ComplaintController::class, 'submitRating'])->name('complaints.rating');

    // Rute polling notifikasi (dibatasi untuk mencegah penyalahgunaan)
    Route::get('/notifications/unread-count', [ComplaintController::class, 'unreadNotificationCount'])
        ->middleware('throttle:notifications')
        ->name('notifications.unread');
    Route::post('/notifications/mark-read', [ComplaintController::class, 'markNotificationsRead'])
        ->middleware('throttle:notifications')
        ->name('notifications.markRead');
    Route::post('/complaints/{complaint}/mark-read', [ComplaintController::class, 'markSingleNotificationRead'])
        ->name('notifications.markSingleRead');
});


// ADMIN ONLY 
Route::middleware(['auth', 'can:admin-only'])->prefix('admin')->group(function () {

    Route::get('/dashboard', [ComplaintController::class, 'adminDashboard'])->name('admin.dashboard');
    Route::get('/complaints', [ComplaintController::class, 'adminIndex'])->name('admin.complaints');
    Route::patch('/complaints/{complaint}/status', [ComplaintController::class, 'updateStatus'])
        ->name('admin.complaints.status');
    Route::delete('/complaints/{complaint}', [ComplaintController::class, 'destroy'])
        ->name('admin.complaints.destroy');

    // Rute polling notifikasi Admin (dibatasi)
    Route::get('/notifications/unread-count', [ComplaintController::class, 'adminUnreadNotificationCount'])
        ->middleware('throttle:notifications')
        ->name('admin.notifications.unread');
    Route::post('/notifications/mark-read', [ComplaintController::class, 'adminMarkNotificationsRead'])
        ->middleware('throttle:notifications')
        ->name('admin.notifications.markRead');
    Route::post('/complaints/{complaint}/mark-read', [ComplaintController::class, 'adminMarkSingleNotificationRead'])
        ->name('admin.notifications.markSingleRead');
});


require __DIR__ . '/auth.php';
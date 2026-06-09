<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Dashboard\DashboardRedirectController;
use App\Http\Controllers\Dashboard\RoleDashboardController;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::get('/catalog', function () {
    return Inertia::render('Placeholder', [
        'title' => 'Каталог',
        'description' => 'Здесь появится каталог готовых услуг исполнителей Таскоры.',
    ]);
})->name('catalog');

Route::get('/tasks', function () {
    return Inertia::render('Placeholder', [
        'title' => 'Задания',
        'description' => 'Здесь заказчики смогут публиковать индивидуальные задания, а исполнители — отправлять отклики.',
    ]);
})->name('tasks');

Route::get('/performers', function () {
    return Inertia::render('Placeholder', [
        'title' => 'Исполнители',
        'description' => 'Здесь будет витрина исполнителей с рейтингами, специализациями и бейджами доверия.',
    ]);
})->name('performers');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');

    Route::get('/customer/dashboard', [RoleDashboardController::class, 'customer'])
        ->middleware('role:customer')
        ->name('customer.dashboard');

    Route::get('/performer/dashboard', [RoleDashboardController::class, 'performer'])
        ->middleware('role:performer')
        ->name('performer.dashboard');

    Route::get('/moderator/dashboard', [RoleDashboardController::class, 'moderator'])
        ->middleware('role:moderator')
        ->name('moderator.dashboard');

    Route::get('/admin/dashboard', [RoleDashboardController::class, 'admin'])
        ->middleware('role:admin')
        ->name('admin.dashboard');
});

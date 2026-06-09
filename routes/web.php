<?php

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

Route::get('/login', function () {
    return Inertia::render('Placeholder', [
        'title' => 'Войти',
        'description' => 'Страница входа будет добавлена на этапе авторизации и ролей.',
    ]);
})->name('login');

Route::get('/register', function () {
    return Inertia::render('Placeholder', [
        'title' => 'Регистрация',
        'description' => 'Страница регистрации будет добавлена на этапе авторизации и ролей.',
    ]);
})->name('register');

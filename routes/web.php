<?php

use Illuminate\Support\Facades\Route;

// Public Landing Page
Route::get('/', function () {
    return view('landing');
})->name('landing');

// Auth Page
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

// Portal Pages
Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/operations', function () {
    return view('operations.index');
})->name('operations');

Route::get('/reports', function () {
    return view('reports.index');
})->name('reports');

Route::get('/harvesting', function () {
    return view('harvesting.index');
})->name('harvesting');

Route::get('/analytics', function () {
    return view('analytics.index');
})->name('analytics');

Route::get('/settings', function () {
    return view('settings.index');
})->name('settings');
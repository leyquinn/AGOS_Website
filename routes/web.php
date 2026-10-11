<?php

use App\Http\Controllers\CitizenReportController;
use Illuminate\Support\Facades\Route;

// Public Landing Page
Route::get('/', function () {
    return view('landing');
})->name('landing');

// Auth Page
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

// Citizen reporting app
Route::middleware('guest')->group(function () {
    Route::get('/citizen/login', [CitizenReportController::class, 'showLogin'])->name('citizen.login');
    Route::post('/citizen/login', [CitizenReportController::class, 'login'])->name('citizen.login.store');
    Route::get('/citizen/register', [CitizenReportController::class, 'showRegistration'])->name('citizen.register');
    Route::post('/citizen/register', [CitizenReportController::class, 'register'])->name('citizen.register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/citizen', [CitizenReportController::class, 'create'])->name('citizen.create');
    Route::post('/citizen/reports', [CitizenReportController::class, 'store'])->name('citizen.reports.store');
    Route::post('/citizen/logout', [CitizenReportController::class, 'logout'])->name('citizen.logout');
});

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
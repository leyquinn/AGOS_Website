<?php

use App\Http\Controllers\Api\AdminManagementController;
use App\Http\Controllers\Api\CitizenApprovalController;
use App\Http\Controllers\CitizenReportController;
use App\Http\Controllers\CitizenMapController;
use App\Http\Controllers\PortalAuthController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;

// Public Landing Page
Route::get('/', function () {
    return view('landing');
})->name('landing');

// Auth Page
Route::get('/login', [PortalAuthController::class, 'create'])->name('login');
Route::post('/login', [PortalAuthController::class, 'store'])->name('login.store');

// Citizen reporting app
Route::get('/citizen/login', [CitizenReportController::class, 'showLogin'])->name('citizen.login');
Route::post('/citizen/login', [CitizenReportController::class, 'login'])->name('citizen.login.store');

Route::middleware('guest')->group(function () {
    Route::get('/citizen/register', [CitizenReportController::class, 'showRegistration'])->name('citizen.register');
    Route::post('/citizen/register', [CitizenReportController::class, 'register'])->name('citizen.register.store');
});
// Handles stale browser history or a manually opened logout URL after a session has ended.
Route::get('/citizen/logout', [CitizenReportController::class, 'logout'])->name('citizen.logout.get');
Route::get('/logout', [PortalAuthController::class, 'destroy'])->name('logout.get');

Route::middleware(['auth', 'role:citizen', 'no-back'])->group(function () {
    Route::get('/citizen', fn () => redirect()->route('citizen.home'))->name('citizen.create');
    Route::get('/citizen/home', [CitizenReportController::class, 'create'])->name('citizen.home');
    Route::get('/citizen/map', [CitizenReportController::class, 'map'])->name('citizen.map');
    Route::get('/citizen/report', [CitizenReportController::class, 'report'])->name('citizen.report');
    Route::get('/citizen/alerts', [CitizenReportController::class, 'alerts'])->name('citizen.alerts');
    Route::get('/citizen/profile', [CitizenReportController::class, 'profile'])->name('citizen.profile');
    Route::get('/api/reports/map', [CitizenMapController::class, 'reports'])->name('citizen.map.reports');
    Route::get('/api/map/search', [CitizenMapController::class, 'search'])->name('citizen.map.search');
    Route::get('/citizen/weather', [WeatherController::class, 'show'])->name('citizen.weather');
    Route::post('/citizen/profile', [CitizenReportController::class, 'updateProfile'])->name('citizen.profile.update');
    Route::post('/citizen/reports', [CitizenReportController::class, 'store'])->name('citizen.reports.store');
    Route::post('/citizen/logout', [CitizenReportController::class, 'logout'])->name('citizen.logout');
});

Route::middleware(['auth', 'role:super_admin', 'no-back'])->prefix('admin')->group(function () {
    Route::get('/barangays', [AdminManagementController::class, 'barangays'])->name('admin.barangays');
    Route::post('/barangay/assign', [AdminManagementController::class, 'assignBarangayAdminAtBarangay'])
        ->name('admin.barangay.assign');
    Route::post('/barangay/revoke/{user}', [AdminManagementController::class, 'revokeBarangayAdmin'])
        ->name('admin.barangay.revoke');
    Route::post('/assign-barangay-admin', [AdminManagementController::class, 'assignBarangayAdminWeb'])
        ->name('admin.assign-barangay-admin');
});

// Portal Pages
Route::middleware(['auth', 'role:super_admin,barangay_admin', 'no-back'])->group(function () {
    Route::get('/weather', [WeatherController::class, 'show'])->name('weather');
    Route::post('/logout', [PortalAuthController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/operations', function () {
        return view('operations.index');
    })->name('operations');

    Route::get('/reports', [ReportsController::class, 'index'])->name('reports');
    Route::get('/notifications', [ReportsController::class, 'notifications'])->name('notifications');

    Route::get('/harvesting', function () {
        return view('harvesting.index');
    })->name('harvesting');

    Route::get('/analytics', function () {
        return view('analytics.index');
    })->name('analytics');

    Route::get('/settings', function () {
        return view('settings.index');
    })->name('settings');
});

Route::middleware(['auth', 'role:barangay_admin', 'no-back'])->prefix('admin')->group(function () {
    Route::get('/citizen-approvals', [CitizenApprovalController::class, 'pendingPage'])
        ->name('admin.citizen-approvals');
    Route::post('/citizen-approvals/{user}/approve', [CitizenApprovalController::class, 'approveWeb'])
        ->name('admin.citizen-approvals.approve');
    Route::post('/citizen-approvals/{user}/decline', [CitizenApprovalController::class, 'declineWeb'])
        ->name('admin.citizen-approvals.decline');
});

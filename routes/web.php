<?php

use App\Http\Controllers\Api\AdminManagementController;
use App\Http\Controllers\Api\CitizenApprovalController;
use App\Http\Controllers\CitizenReportController;
use App\Http\Controllers\HarvestingController;
use App\Http\Controllers\PortalAuthController;
use App\Http\Controllers\ReportsController;
use Illuminate\Support\Facades\Route;

// Public Landing Page
Route::get('/', function () {
    return view('landing');
})->name('landing');

// Auth Page
Route::get('/login', [PortalAuthController::class, 'create'])->name('login');
Route::post('/login', [PortalAuthController::class, 'store'])->name('login.store');

// Citizen reporting app
Route::middleware('guest')->group(function () {
    Route::get('/citizen/login', [CitizenReportController::class, 'showLogin'])->name('citizen.login');
    Route::post('/citizen/login', [CitizenReportController::class, 'login'])->name('citizen.login.store');
    Route::get('/citizen/register', [CitizenReportController::class, 'showRegistration'])->name('citizen.register');
    Route::post('/citizen/register', [CitizenReportController::class, 'register'])->name('citizen.register.store');
});
// Handles stale browser history or a manually opened logout URL after a session has ended.
Route::get('/citizen/logout', [CitizenReportController::class, 'logout'])->name('citizen.logout.get');
Route::get('/logout', [PortalAuthController::class, 'destroy'])->name('logout.get');

Route::middleware(['auth', 'role:citizen', 'no-back'])->group(function () {
    Route::get('/citizen', [CitizenReportController::class, 'create'])->name('citizen.create');
    Route::post('/citizen/profile', [CitizenReportController::class, 'updateProfile'])->name('citizen.profile.update');
    Route::post('/citizen/reports', [CitizenReportController::class, 'store'])->name('citizen.reports.store');
    Route::post('/citizen/logout', [CitizenReportController::class, 'logout'])->name('citizen.logout');
});

Route::middleware(['auth', 'role:super_admin', 'no-back'])->prefix('admin')->group(function () {
    Route::get('/barangays', [AdminManagementController::class, 'barangays'])->name('admin.barangays');
    Route::post('/assign-barangay-admin', [AdminManagementController::class, 'assignBarangayAdminWeb'])
        ->name('admin.assign-barangay-admin');
});

// Portal Pages
Route::middleware(['auth', 'role:super_admin,barangay_admin', 'no-back'])->group(function () {
    Route::post('/logout', [PortalAuthController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/operations', function () {
        return view('operations.index');
    })->name('operations');

    Route::get('/reports', [ReportsController::class, 'index'])->name('reports');
    Route::get('/notifications', [ReportsController::class, 'notifications'])->name('notifications');

    Route::get('/harvesting', [HarvestingController::class, 'index'])->name('harvesting');
    Route::get('/harvesting/status', [HarvestingController::class, 'status'])->name('harvesting.status');
    Route::post('/harvesting/run', [HarvestingController::class, 'runScraper'])->name('harvesting.run');
    Route::post('/harvesting/stop', [HarvestingController::class, 'stopScraper'])->name('harvesting.stop');
    Route::post('/harvesting/clean', [HarvestingController::class, 'runCleaner'])->name('harvesting.clean');
    Route::post('/harvesting/keywords', [HarvestingController::class, 'storeKeyword'])->name('harvesting.keywords.store');
    Route::post('/harvesting/keywords/{keyword}/toggle', [HarvestingController::class, 'toggleKeyword'])->name('harvesting.keywords.toggle');
    Route::delete('/harvesting/keywords/{keyword}', [HarvestingController::class, 'destroyKeyword'])->name('harvesting.keywords.destroy');
    Route::post('/harvesting/sources', [HarvestingController::class, 'storeSource'])->name('harvesting.sources.store');
    Route::delete('/harvesting/sources/{source}', [HarvestingController::class, 'destroySource'])->name('harvesting.sources.destroy');

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

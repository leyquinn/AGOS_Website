<?php

use App\Http\Controllers\Api\AdminManagementController;
use App\Http\Controllers\Api\CitizenAuthController;
use App\Http\Controllers\Api\CitizenReportController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\CitizenApprovalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/citizen/register', [CitizenAuthController::class, 'register']);
    Route::post('/auth/login', [CitizenAuthController::class, 'login']);
    Route::get('/auth/status', [CitizenAuthController::class, 'status'])
        ->middleware('auth:sanctum');
    Route::get('/barangays', [AdminManagementController::class, 'listBarangays']);

    Route::middleware(['auth:sanctum', 'approved'])->get('/user', function (Request $request) {
        $user = $request->user();
        $user->load(['role', 'barangay']);

        return response()->json([
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => trim($user->first_name.' '.$user->last_name),
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'status' => $user->status,
            'rejection_reason' => $user->rejection_reason,
            'role' => $user->role?->name,
            'barangay' => $user->barangay ? [
                'id' => $user->barangay->id,
                'name' => $user->barangay->name,
            ] : null,
        ]);
    });

    Route::middleware(['auth:sanctum', 'approved'])->group(function () {
        Route::get('/analytics/flood-activity', [AnalyticsController::class, 'floodActivity']);
        Route::post('/reports/submit', [CitizenReportController::class, 'store']);
        Route::get('/reports', [CitizenReportController::class, 'index']);

        Route::middleware('role:super_admin')->prefix('admin')->group(function () {
            Route::post('/assign-barangay-admin', [AdminManagementController::class, 'assignBarangayAdmin']);
            Route::get('/barangay-admins', [AdminManagementController::class, 'listBarangayAdmins']);
        });

        Route::middleware('role:barangay_admin')->prefix('admin/citizens')->group(function () {
            Route::get('/pending', [CitizenApprovalController::class, 'index']);
            Route::post('/{user}/approve', [CitizenApprovalController::class, 'approve']);
            Route::post('/{user}/decline', [CitizenApprovalController::class, 'decline']);
        });
    });
});

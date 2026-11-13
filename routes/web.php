<?php

use App\Http\Controllers\Api\AdminManagementController;
use App\Http\Controllers\Api\CitizenApprovalController;
use App\Http\Controllers\BroadcastAlertController;
use App\Http\Controllers\CitizenReportController;
use App\Http\Controllers\CitizenMapController;
use App\Http\Controllers\HarvestingController;
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
Route::get('/api/v1/citizen/alerts', [BroadcastAlertController::class, 'citizenAlerts'])->name('citizen.api.alerts');

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

    Route::get('/operations', function (\Illuminate\Http\Request $request) {
        $heatmapBundle = \Illuminate\Support\Facades\Cache::remember(
            'operations_heatmap_bundle',
            300,
            function () {
                $verifiedAll = \App\Models\SocialPost::where('model_status', 'VERIFIED_ACTIONABLE')
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->select(['id', 'latitude', 'longitude', 'confidence_score', 'primary_barangay', 'discard_reason'])
                    ->get();

                $byBarangay = $verifiedAll->groupBy(fn (\App\Models\SocialPost $p): string => $p->primary_barangay ?: 'Poblacion');
                $barangayReportCounts = $byBarangay->map(fn ($group): int => $group->count())->sortDesc()->all();

                $mapPoints = collect();
                foreach ($byBarangay as $brgy => $group) {
                    $count = $group->count();
                    $avgCs = (float) $group->avg('confidence_score');
                    $maxPts = $count >= 25 ? 4 : ($count >= 5 ? 3 : ($count >= 2 ? 2 : 1));
                    $volumeBoost = min(0.28, log10($count + 1) * 0.12);
                    $intensity = min(0.98, max(0.45, ($avgCs * 0.72) + $volumeBoost));

                    $officialTagged = $group->where('discard_reason', 'verified_by_official');
                    $selected = $officialTagged->merge($group)->unique(fn ($p) => $p->latitude.','.$p->longitude)->take($maxPts);
                    if ($selected->isEmpty()) {
                        $selected = $group->take(1);
                    }

                    foreach ($selected as $pt) {
                        $mapPoints->push([
                            'latitude' => (float) $pt->latitude,
                            'longitude' => (float) $pt->longitude,
                            'intensity' => round($intensity, 3),
                            'barangay' => $brgy,
                            'count' => $count,
                        ]);
                    }
                }

                return [
                    'mapPoints' => $mapPoints->values()->all(),
                    'barangayReportCounts' => $barangayReportCounts,
                    'validatedEventsCount' => $verifiedAll->count(),
                    'barangaysAffectedCount' => $byBarangay->count(),
                    'avgCsOverall' => (float) ($verifiedAll->avg('confidence_score') ?: 0.81),
                ];
            }
        );

        $actionablePosts = \App\Models\SocialPost::where('model_status', 'VERIFIED_ACTIONABLE')
            ->orderByRaw("CASE WHEN discard_reason = 'verified_by_official' THEN 0 ELSE 1 END")
            ->orderByDesc('confidence_score')
            ->latest('scraped_at')
            ->limit(12)
            ->get();

        $reviewBarangay = trim((string) $request->query('review_barangay', ''));
        $noUrgencyQuery = \App\Models\SocialPost::where('model_status', 'FLOOD_MENTION_NO_URGENCY')
            ->orderByDesc('p_rel')
            ->latest('scraped_at');

        if ($reviewBarangay !== '') {
            $noUrgencyQuery->where('primary_barangay', $reviewBarangay);
        }

        $noUrgencyPosts = $noUrgencyQuery->paginate(6, ['*'], 'review_page')->withQueryString();

        $totalActiveReports = \App\Models\SocialPost::whereIn('model_status', ['VERIFIED_ACTIONABLE', 'FLOOD_MENTION_NO_URGENCY'])->count();
        $validatedEventsCount = $heatmapBundle['validatedEventsCount'];
        $barangaysAffectedCount = $heatmapBundle['barangaysAffectedCount'];
        $avgCsOverall = $heatmapBundle['avgCsOverall'];
        $noUrgencyTotalCount = \App\Models\SocialPost::where('model_status', 'FLOOD_MENTION_NO_URGENCY')->count();
        $barangayList = array_keys(\App\Http\Controllers\HarvestingController::getBarangayCentroids());

        return view('operations.index', [
            'actionablePosts' => $actionablePosts,
            'mapPoints' => $heatmapBundle['mapPoints'],
            'barangayReportCounts' => $heatmapBundle['barangayReportCounts'],
            'noUrgencyPosts' => $noUrgencyPosts,
            'noUrgencyTotalCount' => $noUrgencyTotalCount,
            'reviewBarangay' => $reviewBarangay,
            'barangayList' => $barangayList,
            'totalActiveReports' => $totalActiveReports,
            'validatedEventsCount' => $validatedEventsCount,
            'barangaysAffectedCount' => $barangaysAffectedCount,
            'avgCsOverall' => $avgCsOverall,
        ]);
    })->name('operations');

    Route::post('/operations/broadcast', [BroadcastAlertController::class, 'store'])->name('operations.broadcast');

    Route::get('/reports', [ReportsController::class, 'index'])->name('reports');
    Route::get('/notifications', [ReportsController::class, 'notifications'])->name('notifications');

    Route::get('/harvesting', [HarvestingController::class, 'index'])->name('harvesting');
    Route::get('/harvesting/status', [HarvestingController::class, 'status'])->name('harvesting.status');
    Route::post('/harvesting/run', [HarvestingController::class, 'runScraper'])->name('harvesting.run');
    Route::post('/harvesting/stop', [HarvestingController::class, 'stopScraper'])->name('harvesting.stop');
    Route::post('/harvesting/clean', [HarvestingController::class, 'runCleaner'])->name('harvesting.clean');
    Route::post('/harvesting/analyze', [HarvestingController::class, 'runModel'])->name('harvesting.analyze');
    Route::post('/harvesting/posts/{post}/verify', [HarvestingController::class, 'verifyPost'])->name('harvesting.posts.verify');
    Route::post('/harvesting/posts/{post}/dismiss', [HarvestingController::class, 'dismissPost'])->name('harvesting.posts.dismiss');
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

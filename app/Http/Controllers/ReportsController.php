<?php

namespace App\Http\Controllers;

use App\Models\CitizenReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function notifications(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = CitizenReport::with(['user', 'barangay'])->latest();

        if ($user->isBarangayAdmin()) {
            $query->where('barangay_id', $user->barangay_id);
        }

        return response()->json([
            'notifications' => $query->limit(10)->get()->map(fn (CitizenReport $report): array => [
                'id' => $report->id,
                'created_at' => $report->created_at->toIso8601String(),
                'barangay' => $report->barangay?->name ?? 'Unknown barangay',
                'message' => $report->report_text,
                'status' => $report->verification_status,
            ]),
        ]);
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $query = CitizenReport::with(['user', 'barangay'])->latest();

        if ($user->isBarangayAdmin()) {
            $query->where('barangay_id', $user->barangay_id);
        }

        $reports = $query->get();
        $pendingReports = $reports->where('verification_status', 'pending')->values();

        return view('reports.index', [
            'reports' => $reports,
            'pendingReports' => $pendingReports,
            'totalCount' => $reports->count(),
            'pendingCount' => $pendingReports->count(),
            'verifiedCount' => $reports->where('verification_status', 'verified')->count(),
            'rejectedCount' => $reports->where('verification_status', 'rejected')->count(),
            'mapPoints' => $reports
                ->filter(fn (CitizenReport $report): bool => $report->verification_status !== 'rejected' && $report->latitude !== null && $report->longitude !== null)
                ->map(fn (CitizenReport $report): array => [
                    'latitude' => (float) $report->latitude,
                    'longitude' => (float) $report->longitude,
                    'intensity' => $report->verification_status === 'verified' ? 1.0 : 0.65,
                    'barangay' => $report->barangay?->name,
                ])
                ->values(),
        ]);
    }

    public function tag(Request $request, CitizenReport $report): RedirectResponse
    {
        $user = $request->user();
        if ($user->isBarangayAdmin()) {
            abort_unless($report->barangay_id === $user->barangay_id, 403);
        }

        $validated = $request->validate([
            'tag' => ['required', 'in:yes,no'],
        ]);

        $isFlood = $validated['tag'] === 'yes';

        $report->update([
            'verification_status' => $isFlood ? 'verified' : 'rejected',
            'confidence_score' => $isFlood ? 1.0000 : 0.0000,
        ]);

        Cache::forget('operations_heatmap_bundle');

        return back()->with(
            'status',
            $isFlood
                ? 'Report tagged as Yes (Flood Report).'
                : 'Report tagged as No (Not Flood).'
        );
    }
}

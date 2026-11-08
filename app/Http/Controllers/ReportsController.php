<?php

namespace App\Http\Controllers;

use App\Models\CitizenReport;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

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

        return view('reports.index', [
            'reports' => $reports,
            'mapPoints' => $reports
                ->filter(fn (CitizenReport $report): bool => $report->latitude !== null && $report->longitude !== null)
                ->map(fn (CitizenReport $report): array => [
                    'latitude' => (float) $report->latitude,
                    'longitude' => (float) $report->longitude,
                    'intensity' => max((float) $report->confidence_score, 0.35),
                    'barangay' => $report->barangay?->name,
                ])
                ->values(),
            'clusterReports' => $reports
                ->filter(fn (CitizenReport $report): bool => $report->report_text !== null)
                ->take(6),
        ]);
    }
}

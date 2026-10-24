<?php

namespace App\Http\Controllers;

use App\Models\CitizenReport;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportsController extends Controller
{
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
                ])
                ->values(),
            'clusterReports' => $reports
                ->filter(fn (CitizenReport $report): bool => $report->report_text !== null)
                ->take(6),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\DashboardMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardMetricsService $metrics)
    {
    }

    public function index(): View
    {
        $overview = $this->metrics->getOverview();

        $allPeriods = [
            '7d' => $this->metrics->getActivityByPeriod('7d'),
            '4w' => $this->metrics->getActivityByPeriod('4w'),
            '12m' => $this->metrics->getActivityByPeriod('12m'),
            'ytd' => $this->metrics->getActivityByPeriod('ytd'),
        ];

        return view('dashboard', array_merge($overview, [
            'chartDatasets' => $allPeriods,
        ]));
    }

    public function activity(Request $request): JsonResponse
    {
        $period = (string) $request->query('period', '7d');
        abort_unless(in_array($period, ['7d', '4w', '12m', 'ytd'], true), 422, 'Unsupported analytics period.');

        return response()->json([
            'period' => $period,
            'data' => $this->metrics->getActivityByPeriod($period),
        ]);
    }
}


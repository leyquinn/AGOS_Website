<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(private readonly DashboardMetricsService $metrics)
    {
    }

    public function floodActivity(Request $request): JsonResponse
    {
        $period = (string) $request->query('period', '7d');

        abort_unless(in_array($period, ['7d', '4w', '12m', 'ytd'], true), 422, 'Unsupported analytics period.');

        return response()->json([
            'period' => $period,
            'data' => $this->metrics->getActivityByPeriod($period),
        ]);
    }
}

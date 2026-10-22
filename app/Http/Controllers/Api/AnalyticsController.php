<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function floodActivity(Request $request): JsonResponse
    {
        $period = $request->query('period', '7d');

        $datasets = [
            '7d' => [
                'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                'rawReports' => [12, 45, 120, 85, 30, 15, 8],
                'validatedAlerts' => [2, 10, 38, 24, 6, 2, 0],
                'confidence' => ['high' => 86, 'lower' => 49],
                'barangays' => ['labels' => ['Poblacion', 'Matina', 'Talomo', 'Buhangin', 'Agdao'], 'values' => [38, 31, 24, 19, 14]],
                'sources' => ['labels' => ['Citizen App', 'Social Feeds', 'Authority Overrides'], 'values' => [58, 29, 13]],
            ],
            '4w' => [
                'labels' => ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                'rawReports' => [140, 480, 210, 95],
                'validatedAlerts' => [45, 160, 62, 20],
                'confidence' => ['high' => 302, 'lower' => 121],
                'barangays' => ['labels' => ['Matina', 'Poblacion', 'Talomo', 'Buhangin', 'Agdao'], 'values' => [164, 142, 108, 84, 61]],
                'sources' => ['labels' => ['Citizen App', 'Social Feeds', 'Authority Overrides'], 'values' => [51, 34, 15]],
            ],
            '12m' => [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                'rawReports' => [320, 150, 90, 210, 650, 890, 1100, 750, 430, 310, 280, 490],
                'validatedAlerts' => [90, 30, 15, 50, 210, 310, 420, 240, 110, 80, 70, 130],
                'confidence' => ['high' => 1745, 'lower' => 1030],
                'barangays' => ['labels' => ['Talomo', 'Matina', 'Poblacion', 'Buhangin', 'Agdao'], 'values' => [462, 418, 385, 341, 297]],
                'sources' => ['labels' => ['Citizen App', 'Social Feeds', 'Authority Overrides'], 'values' => [47, 38, 15]],
            ],
            'ytd' => [
                'labels' => ['2023', '2024', '2025', '2026'],
                'rawReports' => [4200, 5800, 6100, 2400],
                'validatedAlerts' => [1200, 1850, 2100, 820],
                'confidence' => ['high' => 3960, 'lower' => 1890],
                'barangays' => ['labels' => ['Poblacion', 'Talomo', 'Matina', 'Buhangin', 'Agdao'], 'values' => [920, 804, 731, 665, 583]],
                'sources' => ['labels' => ['Citizen App', 'Social Feeds', 'Authority Overrides'], 'values' => [54, 31, 15]],
            ],
        ];

        abort_unless(array_key_exists($period, $datasets), 422, 'Unsupported analytics period.');

        /*
         * Replace this static dataset with the Python XLM-RoBERTa classification
         * and K-Means++ spatial clustering queries/cache when the ML backend is live.
         */
        return response()->json(['period' => $period, 'data' => $datasets[$period]]);
    }
}

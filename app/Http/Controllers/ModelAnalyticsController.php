<?php

namespace App\Http\Controllers;

use App\Services\ModelAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModelAnalyticsController extends Controller
{
    public function __construct(private readonly ModelAnalyticsService $analytics)
    {
    }

    public function index(): View
    {
        $windows = $this->analytics->getAllWindows();
        $defaultWindow = 'all';

        return view('analytics.index', [
            'analyticsWindows' => $windows,
            'defaultWindow' => $defaultWindow,
            'initial' => $windows[$defaultWindow],
            'checkpoint' => $this->analytics->getCheckpointInfo(),
            'windowLabels' => ModelAnalyticsService::windows(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $window = (string) $request->query('window', 'all');
        abort_unless(
            array_key_exists($window, ModelAnalyticsService::windows()),
            422,
            'Unsupported model analytics window.'
        );

        return response()->json([
            'window' => $window,
            'data' => $this->analytics->getAnalyticsForWindow($window),
        ]);
    }
}


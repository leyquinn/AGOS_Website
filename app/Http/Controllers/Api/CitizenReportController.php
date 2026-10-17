<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CitizenReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CitizenReportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report_text' => ['required', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'water_level_status' => ['required', 'in:low,knee_deep,waist_deep,chest_deep,roof_top'],
            'barangay_id' => ['required', 'exists:barangays,id'],
        ]);

        $report = CitizenReport::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['report' => $report->load(['user', 'barangay'])], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = CitizenReport::with(['user', 'barangay'])->latest();

        if ($user->isBarangayAdmin()) {
            $query->where('barangay_id', $user->barangay_id);
        } elseif ($user->isCitizen()) {
            $query->where('user_id', $user->id);
        }

        return response()->json($query->paginate());
    }
}

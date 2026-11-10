<?php

namespace App\Http\Controllers;

use App\Models\CitizenReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CitizenMapController extends Controller
{
    public function reports(): JsonResponse
    {
        return response()->json([
            'reports' => CitizenReport::with('barangay')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->whereIn('verification_status', ['pending', 'verified'])
                ->latest()
                ->limit(300)
                ->get()
                ->map(fn (CitizenReport $report): array => [
                    'latitude' => (float) $report->latitude,
                    'longitude' => (float) $report->longitude,
                    'location' => $report->barangay?->name ?? 'Reported location',
                    'water_level' => $this->waterLevel($report->water_level_status),
                    'severity' => $this->severity($report->water_level_status),
                    'reported_at' => $report->created_at->diffForHumans(),
                    'validation_status' => ucfirst($report->verification_status),
                ]),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q'));
        if (mb_strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $response = Http::acceptJson()
            ->withHeaders(['User-Agent' => 'AGOS Flood Reporting System'])
            ->timeout(5)
            ->get('https://nominatim.openstreetmap.org/search', [
                'q' => $query.', Davao City, Philippines',
                'format' => 'jsonv2',
                'limit' => 5,
                'addressdetails' => 1,
            ]);

        if (! $response->successful()) {
            return response()->json(['results' => []]);
        }

        return response()->json([
            'results' => collect($response->json())->map(fn (array $result): array => [
                'label' => $result['display_name'] ?? $query,
                'latitude' => (float) $result['lat'],
                'longitude' => (float) $result['lon'],
            ])->values(),
        ]);
    }

    private function waterLevel(?string $status): string
    {
        return match ($status) {
            'low' => 'Gutter deep',
            'knee_deep' => 'Knee deep',
            'waist_deep' => 'Waist deep',
            'chest_deep' => 'Chest deep',
            'roof_top' => 'Roof deep',
            'impassable' => 'Impassable',
            default => 'Water level unavailable',
        };
    }

    private function severity(?string $status): string
    {
        return match ($status) {
            'low' => 'low',
            'knee_deep', 'waist_deep' => 'moderate',
            'chest_deep', 'roof_top', 'impassable' => 'critical',
            default => 'moderate',
        };
    }
}

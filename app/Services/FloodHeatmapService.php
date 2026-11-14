<?php

namespace App\Services;

use App\Models\CitizenReport;
use App\Models\SocialPost;
use Illuminate\Support\Facades\Cache;

class FloodHeatmapService
{
    public const CACHE_KEY = 'operations_heatmap_bundle';

    public const CACHE_TTL_SECONDS = 300;

    /**
     * Build and cache the verified flood heatmap bundle shared across operations and citizen maps.
     *
     * @return array{
     *     mapPoints: array<int, array{latitude: float, longitude: float, intensity: float, barangay: string, count: int}>,
     *     barangayReportCounts: array<string, int>,
     *     validatedEventsCount: int,
     *     barangaysAffectedCount: int,
     *     avgCsOverall: float
     * }
     */
    public function getBundle(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL_SECONDS,
            function (): array {
                $verifiedAll = SocialPost::where('model_status', 'VERIFIED_ACTIONABLE')
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->select(['id', 'latitude', 'longitude', 'confidence_score', 'primary_barangay', 'discard_reason'])
                    ->get();

                $verifiedCitizen = CitizenReport::with('barangay')
                    ->where('verification_status', 'verified')
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->get(['id', 'barangay_id', 'latitude', 'longitude', 'confidence_score']);

                $byBarangay = $verifiedAll->groupBy(fn (SocialPost $p): string => $p->primary_barangay ?: 'Poblacion');
                $citizenByBarangay = $verifiedCitizen->groupBy(fn (CitizenReport $r): string => $r->barangay?->name ?: 'Poblacion');

                $allBarangays = $byBarangay->keys()->merge($citizenByBarangay->keys())->unique();

                $barangayReportCounts = [];
                $mapPoints = collect();

                foreach ($allBarangays as $brgy) {
                    $socialGroup = $byBarangay->get($brgy, collect());
                    $citizenGroup = $citizenByBarangay->get($brgy, collect());
                    $count = $socialGroup->count() + $citizenGroup->count();
                    $barangayReportCounts[$brgy] = $count;

                    $avgCs = $socialGroup->isNotEmpty()
                        ? (float) $socialGroup->avg('confidence_score')
                        : 1.0;
                    $maxPts = $count >= 25 ? 4 : ($count >= 5 ? 3 : ($count >= 2 ? 2 : 1));
                    $volumeBoost = min(0.28, log10($count + 1) * 0.12);
                    $intensity = min(0.98, max(0.45, ($avgCs * 0.72) + $volumeBoost));

                    $officialTagged = $socialGroup->where('discard_reason', 'verified_by_official');
                    $selectedSocial = $officialTagged->merge($socialGroup)
                        ->unique(fn ($p) => $p->latitude.','.$p->longitude)
                        ->take($maxPts);

                    foreach ($selectedSocial as $pt) {
                        $mapPoints->push([
                            'latitude' => (float) $pt->latitude,
                            'longitude' => (float) $pt->longitude,
                            'intensity' => round($intensity, 3),
                            'barangay' => $brgy,
                            'count' => $count,
                        ]);
                    }

                    foreach ($citizenGroup as $cr) {
                        $mapPoints->push([
                            'latitude' => (float) $cr->latitude,
                            'longitude' => (float) $cr->longitude,
                            'intensity' => round(max($intensity, 0.85), 3),
                            'barangay' => $brgy,
                            'count' => $count,
                        ]);
                    }
                }

                arsort($barangayReportCounts);

                return [
                    'mapPoints' => $mapPoints->values()->all(),
                    'barangayReportCounts' => $barangayReportCounts,
                    'validatedEventsCount' => $verifiedAll->count() + $verifiedCitizen->count(),
                    'barangaysAffectedCount' => $allBarangays->count(),
                    'avgCsOverall' => (float) ($verifiedAll->avg('confidence_score') ?: 0.81),
                ];
            }
        );
    }
}


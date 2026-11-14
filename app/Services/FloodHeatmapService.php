<?php

namespace App\Services;

use App\Models\CitizenReport;
use App\Models\SocialPost;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FloodHeatmapService
{
    public const CACHE_KEY = 'operations_heatmap_bundle';

    public const CACHE_TTL_SECONDS = 300;

    public static function parseYear(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $year = (int) $value;

        return ($year >= 1900 && $year <= 2100) ? $year : null;
    }

    public static function parseMonth(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $month = (int) $value;

        return ($month >= 1 && $month <= 12) ? $month : null;
    }

    /**
     * Build and cache verified flood items, then return the heatmap bundle filtered by optional year and month.
     *
     * @return array{
     *     mapPoints: array<int, array{latitude: float, longitude: float, intensity: float, barangay: string, count: int}>,
     *     barangayReportCounts: array<string, int>,
     *     validatedEventsCount: int,
     *     barangaysAffectedCount: int,
     *     avgCsOverall: float,
     *     availablePeriods: array{
     *         years: array<int, array{year: int, count: int}>,
     *         months: array<int, array{year: int, month: int, label: string, month_name: string, count: int}>
     *     },
     *     selectedYear: int|null,
     *     selectedMonth: int|null
     * }
     */
    public function getBundle(?int $year = null, ?int $month = null): array
    {
        $month = ($month !== null && $month >= 1 && $month <= 12) ? $month : null;
        $year = ($year !== null && $year >= 1900 && $year <= 2100) ? $year : null;

        $base = Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL_SECONDS,
            fn (): array => $this->buildCachedDataset()
        );

        if (! is_array($base) || ! isset($base['socialItems'], $base['citizenItems'], $base['periods'])) {
            Cache::forget(self::CACHE_KEY);
            $base = Cache::remember(
                self::CACHE_KEY,
                self::CACHE_TTL_SECONDS,
                fn (): array => $this->buildCachedDataset()
            );
        }

        $matchesPeriod = static function (array $item) use ($year, $month): bool {
            if ($year !== null && $item['year'] !== $year) {
                return false;
            }
            if ($month !== null && $item['month'] !== $month) {
                return false;
            }

            return true;
        };

        $socialItems = collect($base['socialItems'])->filter($matchesPeriod)->values();
        $citizenItems = collect($base['citizenItems'])->filter($matchesPeriod)->values();

        $byBarangay = $socialItems->groupBy('barangay');
        $citizenByBarangay = $citizenItems->groupBy('barangay');

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

            $officialTagged = $socialGroup->where('is_official', true);
            $selectedSocial = $officialTagged->merge($socialGroup)
                ->unique(fn (array $p): string => $p['latitude'].','.$p['longitude'])
                ->take($maxPts);

            foreach ($selectedSocial as $pt) {
                $mapPoints->push([
                    'latitude' => $pt['latitude'],
                    'longitude' => $pt['longitude'],
                    'intensity' => round($intensity, 3),
                    'barangay' => $brgy,
                    'count' => $count,
                ]);
            }

            foreach ($citizenGroup as $cr) {
                $mapPoints->push([
                    'latitude' => $cr['latitude'],
                    'longitude' => $cr['longitude'],
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
            'validatedEventsCount' => $socialItems->count() + $citizenItems->count(),
            'barangaysAffectedCount' => $allBarangays->count(),
            'avgCsOverall' => (float) ($socialItems->avg('confidence_score') ?: 0.81),
            'availablePeriods' => $base['periods'],
            'selectedYear' => $year,
            'selectedMonth' => $month,
        ];
    }

    /**
     * Build sanitized, read-only verified flood reports for public display without exposing reporter identities.
     *
     * @return array<int, array{barangay: string, excerpt: string, confidence: string, sourceLabel: string, reportedAt: string}>
     */
    public function getPublicReportsFeed(?int $year = null, ?int $month = null, int $limit = 10): array
    {
        $matchesPeriod = static function (?Carbon $timestamp) use ($year, $month): bool {
            if (! $timestamp) {
                return $year === null && $month === null;
            }
            if ($year !== null && (int) $timestamp->year !== $year) {
                return false;
            }
            if ($month !== null && (int) $timestamp->month !== $month) {
                return false;
            }

            return true;
        };

        $socialPosts = SocialPost::where('model_status', 'VERIFIED_ACTIONABLE')
            ->latest('scraped_at')
            ->limit(60)
            ->get(['primary_barangay', 'cleaned_text', 'full_text', 'confidence_score', 'discard_reason', 'is_whitelisted_source', 'posted_at', 'scraped_at', 'created_at'])
            ->filter(fn (SocialPost $post): bool => $matchesPeriod($post->posted_at ?? $post->scraped_at ?? $post->created_at))
            ->map(function (SocialPost $post): array {
                $timestamp = $post->posted_at ?? $post->scraped_at ?? $post->created_at;
                $isOfficial = $post->discard_reason === 'verified_by_official';

                return [
                    'ts' => $timestamp?->timestamp ?? 0,
                    'barangay' => $post->primary_barangay ?: 'Davao City',
                    'excerpt' => Str::limit(trim((string) ($post->cleaned_text ?: $post->full_text)), 145),
                    'confidence' => number_format((float) $post->confidence_score, 2),
                    'sourceLabel' => $isOfficial ? 'Official Verified' : ($post->is_whitelisted_source ? 'LGU / News Feed' : 'Validated Feed'),
                    'reportedAt' => $timestamp ? $timestamp->format('M j, Y') : 'Recent',
                ];
            });

        $citizenReports = CitizenReport::with('barangay')
            ->where('verification_status', 'verified')
            ->latest()
            ->limit(40)
            ->get(['barangay_id', 'report_text', 'water_level_status', 'confidence_score', 'created_at'])
            ->filter(fn (CitizenReport $report): bool => $matchesPeriod($report->created_at))
            ->map(fn (CitizenReport $report): array => [
                'ts' => $report->created_at?->timestamp ?? 0,
                'barangay' => $report->barangay?->name ?: 'Davao City',
                'excerpt' => Str::limit(trim((string) ($report->report_text ?: ucfirst(str_replace('_', ' ', (string) $report->water_level_status)).' water level reported.')), 145),
                'confidence' => number_format((float) ($report->confidence_score ?? 0.90), 2),
                'sourceLabel' => 'Citizen Report',
                'reportedAt' => $report->created_at ? $report->created_at->format('M j, Y') : 'Recent',
            ]);

        return $socialPosts
            ->concat($citizenReports)
            ->sortByDesc('ts')
            ->take($limit)
            ->map(fn (array $item): array => [
                'barangay' => $item['barangay'],
                'excerpt' => $item['excerpt'],
                'confidence' => $item['confidence'],
                'sourceLabel' => $item['sourceLabel'],
                'reportedAt' => $item['reportedAt'],
            ])
            ->values()
            ->all();
    }

    /**
     * Query verified social and citizen flood reports, sort chronologically (newest first), and index available year/month periods.
     *
     * @return array{
     *     socialItems: array<int, array<string, mixed>>,
     *     citizenItems: array<int, array<string, mixed>>,
     *     periods: array{
     *         years: array<int, array{year: int, count: int}>,
     *         months: array<int, array{year: int, month: int, label: string, month_name: string, count: int}>
     *     }
     * }
     */
    private function buildCachedDataset(): array
    {
        $verifiedAll = SocialPost::where('model_status', 'VERIFIED_ACTIONABLE')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->select([
                'id',
                'latitude',
                'longitude',
                'confidence_score',
                'primary_barangay',
                'discard_reason',
                'posted_at',
                'scraped_at',
                'created_at',
            ])
            ->get();

        $verifiedCitizen = CitizenReport::with('barangay')
            ->where('verification_status', 'verified')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['id', 'barangay_id', 'latitude', 'longitude', 'confidence_score', 'created_at']);

        $yearCounts = [];
        $monthCounts = [];

        $recordPeriod = static function (?Carbon $timestamp) use (&$yearCounts, &$monthCounts): array {
            if (! $timestamp) {
                return ['year' => null, 'month' => null, 'ts' => 0];
            }

            $y = (int) $timestamp->year;
            $m = (int) $timestamp->month;
            $yearCounts[$y] = ($yearCounts[$y] ?? 0) + 1;
            $key = sprintf('%04d-%02d', $y, $m);
            $monthCounts[$key] = [
                'year' => $y,
                'month' => $m,
                'label' => $timestamp->format('F Y'),
                'month_name' => $timestamp->format('F'),
                'count' => ($monthCounts[$key]['count'] ?? 0) + 1,
            ];

            return ['year' => $y, 'month' => $m, 'ts' => $timestamp->timestamp];
        };

        $socialItems = $verifiedAll
            ->map(function (SocialPost $post) use ($recordPeriod): array {
                $timestamp = $post->posted_at ?? $post->scraped_at ?? $post->created_at;
                $period = $recordPeriod($timestamp);

                return [
                    'latitude' => (float) $post->latitude,
                    'longitude' => (float) $post->longitude,
                    'confidence_score' => (float) $post->confidence_score,
                    'barangay' => $post->primary_barangay ?: 'Poblacion',
                    'is_official' => $post->discard_reason === 'verified_by_official',
                    'year' => $period['year'],
                    'month' => $period['month'],
                    'ts' => $period['ts'],
                ];
            })
            ->sort(fn (array $a, array $b): int => [$b['year'] ?? 0, $b['month'] ?? 0, $b['ts']] <=> [$a['year'] ?? 0, $a['month'] ?? 0, $a['ts']])
            ->values()
            ->all();

        $citizenItems = $verifiedCitizen
            ->map(function (CitizenReport $report) use ($recordPeriod): array {
                $period = $recordPeriod($report->created_at);

                return [
                    'latitude' => (float) $report->latitude,
                    'longitude' => (float) $report->longitude,
                    'confidence_score' => (float) $report->confidence_score,
                    'barangay' => $report->barangay?->name ?: 'Poblacion',
                    'year' => $period['year'],
                    'month' => $period['month'],
                    'ts' => $period['ts'],
                ];
            })
            ->sort(fn (array $a, array $b): int => [$b['year'] ?? 0, $b['month'] ?? 0, $b['ts']] <=> [$a['year'] ?? 0, $a['month'] ?? 0, $a['ts']])
            ->values()
            ->all();

        krsort($yearCounts);
        krsort($monthCounts);

        $years = [];
        foreach ($yearCounts as $y => $cnt) {
            $years[] = ['year' => (int) $y, 'count' => $cnt];
        }

        return [
            'socialItems' => $socialItems,
            'citizenItems' => $citizenItems,
            'periods' => [
                'years' => $years,
                'months' => array_values($monthCounts),
            ],
        ];
    }
}

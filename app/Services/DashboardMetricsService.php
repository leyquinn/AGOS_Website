<?php

namespace App\Services;

use App\Models\BroadcastAlert;
use App\Models\CitizenReport;
use App\Models\HarvestRun;
use App\Models\SocialPost;
use App\Models\VerifiedSource;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardMetricsService
{
    /**
     * Gather top-level KPIs, system health, recent validated reports, and pipeline summary.
     *
     * @return array<string, mixed>
     */
    public function getOverview(): array
    {
        $totalPosts = SocialPost::count();
        $citizenReportsCount = CitizenReport::count();

        $activeReportsCount = SocialPost::whereIn('model_status', [
            'VERIFIED_ACTIONABLE',
            'FLOOD_MENTION_NO_URGENCY',
        ])->count() + $citizenReportsCount;

        $recent7dReportsCount = SocialPost::whereRaw('COALESCE(scraped_at, created_at) >= ?', [now()->subDays(7)])->count()
            + CitizenReport::where('created_at', '>=', now()->subDays(7))->count();

        $validatedEventsCount = SocialPost::where('model_status', 'VERIFIED_ACTIONABLE')->count();
        $awaitingReviewCount = SocialPost::where('model_status', 'FLOOD_MENTION_NO_URGENCY')->count();

        $affectedByBarangay = SocialPost::where('model_status', 'VERIFIED_ACTIONABLE')
            ->whereNotNull('primary_barangay')
            ->selectRaw('primary_barangay, COUNT(*) as total')
            ->groupBy('primary_barangay')
            ->get();

        $barangaysAffectedCount = $affectedByBarangay->count();
        $highPriorityBarangays = $affectedByBarangay->where('total', '>=', 20)->count();

        $avgConfidence = (float) (
            SocialPost::where('model_status', 'VERIFIED_ACTIONABLE')->avg('confidence_score')
            ?? SocialPost::where('is_flood_related', true)->avg('confidence_score')
            ?? 0.0
        );

        $highConfidenceCount = SocialPost::where('confidence_score', '>=', 0.85)->count();

        $pendingCleanCount = SocialPost::where('clean_status', 'pending')->count();
        $pendingModelCount = SocialPost::where('clean_status', 'kept')
            ->where(function ($q) {
                $q->whereNull('analyzed_at')
                    ->orWhere('model_status', 'pending');
            })->count();

        $analyzedPostsCount = SocialPost::whereNotNull('analyzed_at')->count();
        $avgPRel = (float) (SocialPost::where('is_flood_related', true)->avg('p_rel') ?? 0.0);

        $latestHarvest = HarvestRun::latest('id')->first();
        $lastSyncRaw = SocialPost::max('analyzed_at') ?? $latestHarvest?->created_at;
        $lastSyncFormatted = $lastSyncRaw
            ? Carbon::parse($lastSyncRaw)->timezone(config('app.timezone', 'Asia/Manila'))->format('M d, H:i:s')
            : now()->format('M d, H:i:s');

        $recentValidatedReports = SocialPost::where('model_status', 'VERIFIED_ACTIONABLE')
            ->orderByRaw("CASE WHEN discard_reason = 'verified_by_official' THEN 0 ELSE 1 END")
            ->orderByDesc(DB::raw('COALESCE(scraped_at, created_at)'))
            ->limit(4)
            ->get();

        return [
            'lastSyncFormatted' => $lastSyncFormatted,
            'activeReportsCount' => $activeReportsCount,
            'recent7dReportsCount' => $recent7dReportsCount,
            'validatedEventsCount' => $validatedEventsCount,
            'awaitingReviewCount' => $awaitingReviewCount,
            'barangaysAffectedCount' => $barangaysAffectedCount,
            'highPriorityBarangays' => $highPriorityBarangays,
            'avgConfidence' => $avgConfidence,
            'highConfidenceCount' => $highConfidenceCount,
            'pendingCleanCount' => $pendingCleanCount,
            'pendingModelCount' => $pendingModelCount,
            'verifiedSourcesCount' => VerifiedSource::count(),
            'activeBroadcastsCount' => BroadcastAlert::where('is_active', true)->count(),
            'latestHarvestStatus' => $latestHarvest?->status ?? 'ready',
            'recentValidatedReports' => $recentValidatedReports,
            'totalPosts' => $totalPosts,
            'analyzedPostsCount' => $analyzedPostsCount,
            'avgPRel' => $avgPRel,
            'checkpointReady' => is_file(config('services.agos_scraper.model_checkpoint_path', base_path('intelligence/model/checkpoints/best_model.pt'))),
        ];
    }

    /**
     * Build Chart.js dataset for a given period ('7d', '4w', '12m', 'ytd').
     *
     * @return array<string, mixed>
     */
    public function getActivityByPeriod(string $period): array
    {
        $now = now();

        if ($period === '7d') {
            $start = $now->copy()->subDays(6)->startOfDay();
            $buckets = [];
            for ($i = 6; $i >= 0; $i--) {
                $day = $now->copy()->subDays($i);
                $buckets[$day->format('Y-m-d')] = [
                    'label' => $day->format('M d'),
                    'raw' => 0,
                    'validated' => 0,
                ];
            }

            $socialRows = SocialPost::whereRaw('COALESCE(scraped_at, created_at) >= ?', [$start])
                ->selectRaw('DATE(COALESCE(scraped_at, created_at)) as bucket, COUNT(*) as raw_cnt, SUM(CASE WHEN model_status = "VERIFIED_ACTIONABLE" THEN 1 ELSE 0 END) as val_cnt')
                ->groupBy('bucket')
                ->get();

            foreach ($socialRows as $row) {
                if (isset($buckets[$row->bucket])) {
                    $buckets[$row->bucket]['raw'] += (int) $row->raw_cnt;
                    $buckets[$row->bucket]['validated'] += (int) $row->val_cnt;
                }
            }

            $citizenRows = CitizenReport::where('created_at', '>=', $start)
                ->selectRaw('DATE(created_at) as bucket, COUNT(*) as cnt')
                ->groupBy('bucket')
                ->get();

            foreach ($citizenRows as $row) {
                if (isset($buckets[$row->bucket])) {
                    $buckets[$row->bucket]['raw'] += (int) $row->cnt;
                }
            }

            return $this->finalizeChartPayload($buckets, $start);
        }

        if ($period === '4w') {
            $start = $now->copy()->subDays(27)->startOfDay();
            $buckets = [];
            $ranges = [];
            for ($w = 3; $w >= 0; $w--) {
                $wEnd = $now->copy()->subDays($w * 7)->endOfDay();
                $wStart = $wEnd->copy()->subDays(6)->startOfDay();
                $key = "w_{$w}";
                $ranges[$key] = [$wStart, $wEnd];
                $buckets[$key] = [
                    'label' => $wStart->format('M d').'–'.$wEnd->format('d'),
                    'raw' => 0,
                    'validated' => 0,
                ];
            }

            $posts = SocialPost::whereRaw('COALESCE(scraped_at, created_at) >= ?', [$start])
                ->select(['scraped_at', 'created_at', 'model_status'])
                ->get();

            foreach ($posts as $post) {
                $ts = $post->scraped_at ?? $post->created_at;
                foreach ($ranges as $key => [$rStart, $rEnd]) {
                    if ($ts->between($rStart, $rEnd)) {
                        $buckets[$key]['raw']++;
                        if ($post->model_status === 'VERIFIED_ACTIONABLE') {
                            $buckets[$key]['validated']++;
                        }
                        break;
                    }
                }
            }

            $citizenPosts = CitizenReport::where('created_at', '>=', $start)->get(['created_at']);
            foreach ($citizenPosts as $cPost) {
                foreach ($ranges as $key => [$rStart, $rEnd]) {
                    if ($cPost->created_at->between($rStart, $rEnd)) {
                        $buckets[$key]['raw']++;
                        break;
                    }
                }
            }

            return $this->finalizeChartPayload($buckets, $start);
        }

        if ($period === '12m') {
            $start = $now->copy()->subMonths(11)->startOfMonth();
            $buckets = [];
            for ($m = 11; $m >= 0; $m--) {
                $month = $now->copy()->subMonths($m);
                $buckets[$month->format('Y-m')] = [
                    'label' => $month->format("M 'y"),
                    'raw' => 0,
                    'validated' => 0,
                ];
            }

            $socialRows = SocialPost::whereRaw('COALESCE(scraped_at, created_at) >= ?', [$start])
                ->selectRaw('DATE_FORMAT(COALESCE(scraped_at, created_at), "%Y-%m") as bucket, COUNT(*) as raw_cnt, SUM(CASE WHEN model_status = "VERIFIED_ACTIONABLE" THEN 1 ELSE 0 END) as val_cnt')
                ->groupBy('bucket')
                ->get();

            foreach ($socialRows as $row) {
                if (isset($buckets[$row->bucket])) {
                    $buckets[$row->bucket]['raw'] += (int) $row->raw_cnt;
                    $buckets[$row->bucket]['validated'] += (int) $row->val_cnt;
                }
            }

            $citizenRows = CitizenReport::where('created_at', '>=', $start)
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as bucket, COUNT(*) as cnt')
                ->groupBy('bucket')
                ->get();

            foreach ($citizenRows as $row) {
                if (isset($buckets[$row->bucket])) {
                    $buckets[$row->bucket]['raw'] += (int) $row->cnt;
                }
            }

            return $this->finalizeChartPayload($buckets, $start);
        }

        // 'ytd' -> All Years (multi-year historical archive)
        $socialYears = SocialPost::selectRaw('YEAR(COALESCE(scraped_at, created_at)) as bucket, COUNT(*) as raw_cnt, SUM(CASE WHEN model_status = "VERIFIED_ACTIONABLE" THEN 1 ELSE 0 END) as val_cnt')
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        $buckets = [];
        foreach ($socialYears as $row) {
            if (! $row->bucket) {
                continue;
            }
            $yr = (string) $row->bucket;
            $buckets[$yr] = [
                'label' => $yr,
                'raw' => (int) $row->raw_cnt,
                'validated' => (int) $row->val_cnt,
            ];
        }

        $citizenYears = CitizenReport::selectRaw('YEAR(created_at) as bucket, COUNT(*) as cnt')
            ->groupBy('bucket')
            ->get();

        foreach ($citizenYears as $row) {
            $yr = (string) $row->bucket;
            if (isset($buckets[$yr])) {
                $buckets[$yr]['raw'] += (int) $row->cnt;
            }
        }

        return $this->finalizeChartPayload($buckets, null);
    }

    /**
     * Build confidence distribution, top barangays, and source breakdown for the selected period.
     *
     * @param  array<string, array{label: string, raw: int, validated: int}>  $buckets
     * @return array<string, mixed>
     */
    private function finalizeChartPayload(array $buckets, ?Carbon $start): array
    {
        $postScope = SocialPost::query();
        $citizenScope = CitizenReport::query();

        if ($start) {
            $postScope->whereRaw('COALESCE(scraped_at, created_at) >= ?', [$start]);
            $citizenScope->where('created_at', '>=', $start);
        }

        $highConfidence = (clone $postScope)->where('confidence_score', '>=', 0.85)->count();
        $lowerConfidence = (clone $postScope)
            ->where('confidence_score', '<', 0.85)
            ->where('confidence_score', '>', 0)
            ->count();

        // Top 5 affected Davao barangays
        $topBarangays = (clone $postScope)
            ->where('model_status', 'VERIFIED_ACTIONABLE')
            ->whereNotNull('primary_barangay')
            ->selectRaw('primary_barangay, COUNT(*) as total')
            ->groupBy('primary_barangay')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $brgyLabels = $topBarangays->pluck('primary_barangay')->all();
        $brgyValues = $topBarangays->pluck('total')->map(fn ($v) => (int) $v)->all();

        // Source signal breakdown
        $citizenCount = (clone $citizenScope)->count();
        $officialCount = (clone $postScope)
            ->where(function ($q) {
                $q->where('is_whitelisted_source', true)
                    ->orWhere('discard_reason', 'verified_by_official');
            })
            ->count();
        $communitySocialCount = max(0, (clone $postScope)->count() - $officialCount);

        return [
            'labels' => array_column($buckets, 'label'),
            'rawReports' => array_column($buckets, 'raw'),
            'validatedAlerts' => array_column($buckets, 'validated'),
            'confidence' => [
                'high' => $highConfidence,
                'lower' => $lowerConfidence,
            ],
            'barangays' => [
                'labels' => empty($brgyLabels) ? ['No validated alerts'] : $brgyLabels,
                'values' => empty($brgyValues) ? [0] : $brgyValues,
            ],
            'sources' => [
                'labels' => ['Social Feeds (FB)', 'Verified / Official', 'Citizen App'],
                'values' => [$communitySocialCount, $officialCount, $citizenCount],
            ],
        ];
    }
}


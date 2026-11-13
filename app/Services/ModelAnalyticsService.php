<?php

namespace App\Services;

use App\Models\SocialPost;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ModelAnalyticsService
{
    /**
     * Supported time windows for Model Analytics.
     *
     * @return array<string, string>
     */
    public static function windows(): array
    {
        return [
            'all' => 'All Analyzed Posts',
            '12m' => 'Last 12 Months',
            '30d' => 'Last 30 Days',
            '7d' => 'Last 7 Days',
        ];
    }

    /**
     * Return checkpoint file metadata and model hyperparameters from config.
     *
     * @return array<string, mixed>
     */
    public function getCheckpointInfo(): array
    {
        $path = (string) config(
            'services.agos_scraper.model_checkpoint_path',
            base_path('intelligence/model/checkpoints/best_model.pt')
        );
        $exists = is_file($path);
        $sizeBytes = $exists ? (filesize($path) ?: 0) : 0;
        $modifiedAt = $exists ? filemtime($path) : false;

        return [
            'exists' => $exists,
            'filename' => basename($path),
            'sizeGb' => $sizeBytes > 0 ? round($sizeBytes / (1024 ** 3), 2) : 0.0,
            'sizeMb' => $sizeBytes > 0 ? round($sizeBytes / (1024 ** 2), 1) : 0.0,
            'updatedAt' => $modifiedAt
                ? Carbon::createFromTimestamp($modifiedAt)->timezone(config('app.timezone', 'Asia/Manila'))->format('M d, Y H:i')
                : 'Unavailable',
            'encoder' => 'xlm-roberta-base',
            'nerSchema' => 'IOB2 (O, B-LOC, I-LOC)',
            'threshold' => 0.50,
            'csFormula' => '0.35 · P_rel + 0.65 · D_clusters',
            'bestEpoch' => 2,
            'bestValLoss' => 0.2218,
            'lossWeights' => 'α = 1.0 (Classification), β = 1.0 (NER)',
        ];
    }

    /**
     * Build precomputed datasets for every supported window.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAllWindows(): array
    {
        $result = [];
        foreach (array_keys(self::windows()) as $window) {
            $result[$window] = $this->getAnalyticsForWindow($window);
        }

        return $result;
    }

    /**
     * Build live XLM-RoBERTa + K-Means++ telemetry for the given window.
     *
     * @return array<string, mixed>
     */
    public function getAnalyticsForWindow(string $window = 'all'): array
    {
        $start = match ($window) {
            '7d' => now()->subDays(6)->startOfDay(),
            '30d' => now()->subDays(29)->startOfDay(),
            '12m' => now()->subMonths(11)->startOfMonth(),
            default => null,
        };

        $baseQuery = fn (): Builder => $this->scopedQuery($start);
        $analyzedQuery = fn (): Builder => $this->scopedQuery($start)->whereNotNull('analyzed_at');

        $totalInWindow = $baseQuery()->count();
        $analyzedCount = $analyzedQuery()->count();
        $pendingCount = max(0, $totalInWindow - $analyzedCount);
        $analyzedPct = $totalInWindow > 0 ? round(($analyzedCount / $totalInWindow) * 100, 1) : 0.0;

        // Per-status counts & score averages
        $statusRows = $analyzedQuery()
            ->selectRaw("
                COALESCE(model_status, 'PENDING') as status,
                COUNT(*) as cnt,
                AVG(COALESCE(p_rel, 0)) as avg_p_rel,
                AVG(COALESCE(d_clusters, 0)) as avg_d_clusters,
                AVG(COALESCE(confidence_score, 0)) as avg_cs
            ")
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $actionableCount = (int) ($statusRows->get('VERIFIED_ACTIONABLE')->cnt ?? 0);
        $watchCount = (int) ($statusRows->get('FLOOD_MENTION_NO_URGENCY')->cnt ?? 0);
        $discardedNotFloodCount = (int) ($statusRows->get('DISCARDED_NOT_FLOOD')->cnt ?? 0);
        $discardedUnlocalizedCount = (int) ($statusRows->get('DISCARDED_UNLOCALIZED')->cnt ?? 0);
        $discardedTotal = $discardedNotFloodCount + $discardedUnlocalizedCount;

        $denom = max(1, $analyzedCount);
        $actionableRate = $analyzedCount > 0 ? round(($actionableCount / $denom) * 100, 1) : 0.0;
        $watchRate = $analyzedCount > 0 ? round(($watchCount / $denom) * 100, 1) : 0.0;
        $discardedRate = $analyzedCount > 0 ? round(($discardedTotal / $denom) * 100, 1) : 0.0;

        $avgActionableCs = round((float) ($statusRows->get('VERIFIED_ACTIONABLE')->avg_cs ?? 0.0), 2);
        $meanPRel = round((float) ($analyzedQuery()->avg('p_rel') ?? 0.0), 2);
        $meanFloodPRel = round((float) ($analyzedQuery()->where('is_flood_related', true)->avg('p_rel') ?? 0.0), 2);
        $meanDClusters = round((float) ($analyzedQuery()->whereNotNull('cluster_id')->where('cluster_id', '>=', 0)->avg('d_clusters') ?? 0.0), 2);
        $meanFloodCs = round((float) ($analyzedQuery()->where('is_flood_related', true)->avg('confidence_score') ?? 0.0), 2);
        $uniqueClusters = (int) $analyzedQuery()->whereNotNull('cluster_id')->where('cluster_id', '>=', 0)->distinct('cluster_id')->count('cluster_id');

        // Pipeline outcome matrix rows
        $statusMeta = [
            'VERIFIED_ACTIONABLE' => ['label' => 'Verified Actionable', 'tone' => 'emerald'],
            'FLOOD_MENTION_NO_URGENCY' => ['label' => 'Flood Watch (Early Warning)', 'tone' => 'amber'],
            'DISCARDED_NOT_FLOOD' => ['label' => 'Discarded (Non-Flood / Noise)', 'tone' => 'rose'],
            'DISCARDED_UNLOCALIZED' => ['label' => 'Discarded (Unlocalized)', 'tone' => 'slate'],
        ];

        $outcomeMatrix = [];
        foreach ($statusMeta as $statusKey => $meta) {
            $row = $statusRows->get($statusKey);
            $cnt = (int) ($row->cnt ?? 0);
            $outcomeMatrix[] = [
                'status' => $statusKey,
                'label' => $meta['label'],
                'tone' => $meta['tone'],
                'count' => $cnt,
                'sharePct' => $analyzedCount > 0 ? round(($cnt / $denom) * 100, 1) : 0.0,
                'avgPRel' => round((float) ($row->avg_p_rel ?? 0.0), 2),
                'avgDClusters' => round((float) ($row->avg_d_clusters ?? 0.0), 2),
                'avgCs' => round((float) ($row->avg_cs ?? 0.0), 2),
            ];
        }

        // Strict filter / gate decision reasons
        $rawReasons = $analyzedQuery()
            ->whereNotNull('discard_reason')
            ->where('discard_reason', '!=', '')
            ->selectRaw('discard_reason, COUNT(*) as cnt')
            ->groupBy('discard_reason')
            ->orderByDesc('cnt')
            ->get();

        $reasonMap = [
            'isolated_flood_mention_no_urgency_cues' => ['label' => 'Flood Mention (No Urgency Cues)', 'gate' => 'Urgency Gate'],
            'no_flood_terms_or_flood_context' => ['label' => 'No Flood Terms or Context', 'gate' => 'Strict Filter'],
            'figurative_or_advertisement' => ['label' => 'Figurative Idiom / Advertisement', 'gate' => 'Strict Filter'],
            'political_campaign_speech' => ['label' => 'Political / Campaign Speech', 'gate' => 'Strict Filter'],
            'lookalike_nonflood_word' => ['label' => 'Lookalike Non-Flood Term', 'gate' => 'Strict Filter'],
            'historical_or_hypothetical_tips' => ['label' => 'Historical / Hypothetical Safety Tips', 'gate' => 'Strict Filter'],
            'missing_davao_location' => ['label' => 'No Davao Barangay Detected (NER/Gazetteer)', 'gate' => 'Spatial Gate'],
            'neural_relevance_below_threshold' => ['label' => 'Neural Relevance P_rel < 0.50', 'gate' => 'XLM-R Threshold'],
            'verified_by_official' => ['label' => 'Verified by Barangay/Admin Reviewer', 'gate' => 'HITL Audit'],
            'dismissed_by_official' => ['label' => 'Dismissed by Barangay/Admin Reviewer', 'gate' => 'HITL Audit'],
        ];

        $filterReasons = $rawReasons->map(function ($row) use ($reasonMap, $denom, $analyzedCount) {
            $key = (string) $row->discard_reason;
            $meta = $reasonMap[$key] ?? [
                'label' => ucwords(str_replace('_', ' ', $key)),
                'gate' => 'Pipeline Gate',
            ];
            $cnt = (int) $row->cnt;

            return [
                'code' => $key,
                'label' => $meta['label'],
                'gate' => $meta['gate'],
                'count' => $cnt,
                'sharePct' => $analyzedCount > 0 ? round(($cnt / $denom) * 100, 1) : 0.0,
            ];
        })->values()->all();

        // HITL & official source audit
        $lastAnalyzedRaw = $analyzedQuery()->max('analyzed_at');
        $hitlAudit = [
            'whitelistedCount' => $analyzedQuery()->where('is_whitelisted_source', true)->count(),
            'hitlVerifiedCount' => $analyzedQuery()->where('discard_reason', 'verified_by_official')->count(),
            'hitlDismissedCount' => $analyzedQuery()->where('discard_reason', 'dismissed_by_official')->count(),
            'lastAnalyzedFormatted' => $lastAnalyzedRaw
                ? Carbon::parse($lastAnalyzedRaw)->timezone(config('app.timezone', 'Asia/Manila'))->format('M d, Y H:i')
                : 'Not run yet',
        ];

        // Top 10 barangays by analyzed volume
        $barangays = $analyzedQuery()
            ->whereNotNull('primary_barangay')
            ->where('primary_barangay', '!=', '')
            ->selectRaw('
                primary_barangay,
                COUNT(*) as total,
                SUM(CASE WHEN model_status = "VERIFIED_ACTIONABLE" THEN 1 ELSE 0 END) as actionable,
                SUM(CASE WHEN model_status = "FLOOD_MENTION_NO_URGENCY" THEN 1 ELSE 0 END) as watch,
                AVG(COALESCE(p_rel, 0)) as avg_p_rel,
                AVG(COALESCE(d_clusters, 0)) as avg_d_clusters,
                AVG(COALESCE(confidence_score, 0)) as avg_cs,
                COUNT(DISTINCT CASE WHEN cluster_id >= 0 THEN cluster_id ELSE NULL END) as clusters
            ')
            ->groupBy('primary_barangay')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $total = (int) $row->total;
                $actionable = (int) $row->actionable;

                return [
                    'barangay' => (string) $row->primary_barangay,
                    'total' => $total,
                    'actionable' => $actionable,
                    'watch' => (int) $row->watch,
                    'actionableRate' => $total > 0 ? round(($actionable / $total) * 100, 1) : 0.0,
                    'avgPRel' => round((float) $row->avg_p_rel, 2),
                    'avgDClusters' => round((float) $row->avg_d_clusters, 2),
                    'avgCs' => round((float) $row->avg_cs, 2),
                    'clusters' => (int) $row->clusters,
                ];
            })
            ->values()
            ->all();

        // Chart 1: Multi-task scoring profile across outcome tiers
        $discardAgg = $analyzedQuery()
            ->whereIn('model_status', ['DISCARDED_NOT_FLOOD', 'DISCARDED_UNLOCALIZED'])
            ->selectRaw('AVG(COALESCE(p_rel, 0)) as avg_p_rel, AVG(COALESCE(d_clusters, 0)) as avg_d_clusters, AVG(COALESCE(confidence_score, 0)) as avg_cs')
            ->first();

        $scoringByStatus = [
            'labels' => ['Verified Actionable', 'Flood Watch', 'Discarded'],
            'pRel' => [
                round((float) ($statusRows->get('VERIFIED_ACTIONABLE')->avg_p_rel ?? 0.0), 2),
                round((float) ($statusRows->get('FLOOD_MENTION_NO_URGENCY')->avg_p_rel ?? 0.0), 2),
                round((float) ($discardAgg->avg_p_rel ?? 0.0), 2),
            ],
            'dClusters' => [
                round((float) ($statusRows->get('VERIFIED_ACTIONABLE')->avg_d_clusters ?? 0.0), 2),
                round((float) ($statusRows->get('FLOOD_MENTION_NO_URGENCY')->avg_d_clusters ?? 0.0), 2),
                round((float) ($discardAgg->avg_d_clusters ?? 0.0), 2),
            ],
            'confidence' => [
                round((float) ($statusRows->get('VERIFIED_ACTIONABLE')->avg_cs ?? 0.0), 2),
                round((float) ($statusRows->get('FLOOD_MENTION_NO_URGENCY')->avg_cs ?? 0.0), 2),
                round((float) ($discardAgg->avg_cs ?? 0.0), 2),
            ],
        ];

        // Chart 2: P_rel vs CS histogram across 5 score buckets
        $histRow = $analyzedQuery()
            ->selectRaw('
                SUM(CASE WHEN COALESCE(p_rel, 0) < 0.2 THEN 1 ELSE 0 END) as p_0,
                SUM(CASE WHEN p_rel >= 0.2 AND p_rel < 0.4 THEN 1 ELSE 0 END) as p_1,
                SUM(CASE WHEN p_rel >= 0.4 AND p_rel < 0.6 THEN 1 ELSE 0 END) as p_2,
                SUM(CASE WHEN p_rel >= 0.6 AND p_rel < 0.8 THEN 1 ELSE 0 END) as p_3,
                SUM(CASE WHEN p_rel >= 0.8 THEN 1 ELSE 0 END) as p_4,
                SUM(CASE WHEN COALESCE(confidence_score, 0) < 0.2 THEN 1 ELSE 0 END) as c_0,
                SUM(CASE WHEN confidence_score >= 0.2 AND confidence_score < 0.4 THEN 1 ELSE 0 END) as c_1,
                SUM(CASE WHEN confidence_score >= 0.4 AND confidence_score < 0.6 THEN 1 ELSE 0 END) as c_2,
                SUM(CASE WHEN confidence_score >= 0.6 AND confidence_score < 0.8 THEN 1 ELSE 0 END) as c_3,
                SUM(CASE WHEN confidence_score >= 0.8 THEN 1 ELSE 0 END) as c_4
            ')
            ->first();

        $scoreDistribution = [
            'labels' => ['0.00–0.20', '0.20–0.40', '0.40–0.60', '0.60–0.80', '0.80–1.00'],
            'pRelCounts' => [
                (int) ($histRow->p_0 ?? 0),
                (int) ($histRow->p_1 ?? 0),
                (int) ($histRow->p_2 ?? 0),
                (int) ($histRow->p_3 ?? 0),
                (int) ($histRow->p_4 ?? 0),
            ],
            'csCounts' => [
                (int) ($histRow->c_0 ?? 0),
                (int) ($histRow->c_1 ?? 0),
                (int) ($histRow->c_2 ?? 0),
                (int) ($histRow->c_3 ?? 0),
                (int) ($histRow->c_4 ?? 0),
            ],
        ];

        // Chart 3: River basin breakdown
        $basinRows = $analyzedQuery()
            ->whereNotNull('river_basin')
            ->where('river_basin', '!=', '')
            ->selectRaw('river_basin, COUNT(*) as cnt')
            ->groupBy('river_basin')
            ->orderByDesc('cnt')
            ->limit(7)
            ->get();

        $riverBasins = [
            'labels' => $basinRows->isEmpty() ? ['No localized basins'] : $basinRows->pluck('river_basin')->all(),
            'values' => $basinRows->isEmpty() ? [0] : $basinRows->pluck('cnt')->map(fn ($v) => (int) $v)->all(),
        ];

        // Chart 4: Classification yield over time buckets
        $yieldTrend = $this->buildYieldTrend($window, $start);

        return [
            'window' => $window,
            'windowLabel' => self::windows()[$window] ?? 'All Analyzed Posts',
            'summary' => [
                'totalInWindow' => $totalInWindow,
                'analyzedCount' => $analyzedCount,
                'pendingCount' => $pendingCount,
                'analyzedPct' => $analyzedPct,
                'actionableCount' => $actionableCount,
                'actionableRate' => $actionableRate,
                'avgActionableCs' => $avgActionableCs,
                'watchCount' => $watchCount,
                'watchRate' => $watchRate,
                'discardedTotal' => $discardedTotal,
                'discardedRate' => $discardedRate,
                'meanPRel' => $meanPRel,
                'meanFloodPRel' => $meanFloodPRel,
                'meanDClusters' => $meanDClusters,
                'meanFloodCs' => $meanFloodCs,
                'uniqueClusters' => $uniqueClusters,
            ],
            'outcomeMatrix' => $outcomeMatrix,
            'filterReasons' => $filterReasons,
            'hitlAudit' => $hitlAudit,
            'barangays' => $barangays,
            'charts' => [
                'scoringByStatus' => $scoringByStatus,
                'scoreDistribution' => $scoreDistribution,
                'riverBasins' => $riverBasins,
                'yieldTrend' => $yieldTrend,
            ],
        ];
    }

    private function scopedQuery(?Carbon $start): Builder
    {
        $query = SocialPost::query();
        if ($start) {
            $query->whereRaw('COALESCE(scraped_at, created_at) >= ?', [$start]);
        }

        return $query;
    }

    /**
     * Group pipeline classifications over time buckets matching the selected window.
     *
     * @return array<string, mixed>
     */
    private function buildYieldTrend(string $window, ?Carbon $start): array
    {
        $now = now();
        $buckets = [];

        if ($window === '7d') {
            for ($i = 6; $i >= 0; $i--) {
                $day = $now->copy()->subDays($i);
                $buckets[$day->format('Y-m-d')] = ['label' => $day->format('M d'), 'actionable' => 0, 'watch' => 0, 'discarded' => 0];
            }

            $rows = $this->scopedQuery($start)->whereNotNull('analyzed_at')
                ->selectRaw('
                    DATE(COALESCE(scraped_at, created_at)) as bucket,
                    SUM(CASE WHEN model_status = "VERIFIED_ACTIONABLE" THEN 1 ELSE 0 END) as actionable,
                    SUM(CASE WHEN model_status = "FLOOD_MENTION_NO_URGENCY" THEN 1 ELSE 0 END) as watch,
                    SUM(CASE WHEN model_status IN ("DISCARDED_NOT_FLOOD", "DISCARDED_UNLOCALIZED") THEN 1 ELSE 0 END) as discarded
                ')
                ->groupBy('bucket')
                ->get();

            foreach ($rows as $row) {
                if (isset($buckets[$row->bucket])) {
                    $buckets[$row->bucket]['actionable'] = (int) $row->actionable;
                    $buckets[$row->bucket]['watch'] = (int) $row->watch;
                    $buckets[$row->bucket]['discarded'] = (int) $row->discarded;
                }
            }
        } elseif ($window === '30d') {
            $ranges = [];
            for ($w = 3; $w >= 0; $w--) {
                $wEnd = $now->copy()->subDays($w * 7)->endOfDay();
                $wStart = $wEnd->copy()->subDays(6)->startOfDay();
                $key = "w_{$w}";
                $ranges[$key] = [$wStart, $wEnd];
                $buckets[$key] = ['label' => $wStart->format('M d').'–'.$wEnd->format('d'), 'actionable' => 0, 'watch' => 0, 'discarded' => 0];
            }

            foreach ($ranges as $key => [$wStart, $wEnd]) {
                $agg = SocialPost::whereNotNull('analyzed_at')
                    ->whereRaw('COALESCE(scraped_at, created_at) BETWEEN ? AND ?', [$wStart, $wEnd])
                    ->selectRaw('
                        SUM(CASE WHEN model_status = "VERIFIED_ACTIONABLE" THEN 1 ELSE 0 END) as actionable,
                        SUM(CASE WHEN model_status = "FLOOD_MENTION_NO_URGENCY" THEN 1 ELSE 0 END) as watch,
                        SUM(CASE WHEN model_status IN ("DISCARDED_NOT_FLOOD", "DISCARDED_UNLOCALIZED") THEN 1 ELSE 0 END) as discarded
                    ')
                    ->first();

                $buckets[$key]['actionable'] = (int) ($agg->actionable ?? 0);
                $buckets[$key]['watch'] = (int) ($agg->watch ?? 0);
                $buckets[$key]['discarded'] = (int) ($agg->discarded ?? 0);
            }
        } elseif ($window === '12m') {
            for ($m = 11; $m >= 0; $m--) {
                $mo = $now->copy()->startOfMonth()->subMonths($m);
                $buckets[$mo->format('Y-m')] = ['label' => $mo->format('M Y'), 'actionable' => 0, 'watch' => 0, 'discarded' => 0];
            }

            $rows = $this->scopedQuery($start)->whereNotNull('analyzed_at')
                ->selectRaw('
                    DATE_FORMAT(COALESCE(scraped_at, created_at), "%Y-%m") as bucket,
                    SUM(CASE WHEN model_status = "VERIFIED_ACTIONABLE" THEN 1 ELSE 0 END) as actionable,
                    SUM(CASE WHEN model_status = "FLOOD_MENTION_NO_URGENCY" THEN 1 ELSE 0 END) as watch,
                    SUM(CASE WHEN model_status IN ("DISCARDED_NOT_FLOOD", "DISCARDED_UNLOCALIZED") THEN 1 ELSE 0 END) as discarded
                ')
                ->groupBy('bucket')
                ->get();

            foreach ($rows as $row) {
                if (isset($buckets[$row->bucket])) {
                    $buckets[$row->bucket]['actionable'] = (int) $row->actionable;
                    $buckets[$row->bucket]['watch'] = (int) $row->watch;
                    $buckets[$row->bucket]['discarded'] = (int) $row->discarded;
                }
            }
        } else {
            $rows = SocialPost::whereNotNull('analyzed_at')
                ->selectRaw('
                    YEAR(COALESCE(scraped_at, created_at)) as bucket,
                    SUM(CASE WHEN model_status = "VERIFIED_ACTIONABLE" THEN 1 ELSE 0 END) as actionable,
                    SUM(CASE WHEN model_status = "FLOOD_MENTION_NO_URGENCY" THEN 1 ELSE 0 END) as watch,
                    SUM(CASE WHEN model_status IN ("DISCARDED_NOT_FLOOD", "DISCARDED_UNLOCALIZED") THEN 1 ELSE 0 END) as discarded
                ')
                ->groupBy('bucket')
                ->orderBy('bucket')
                ->get();

            foreach ($rows as $row) {
                if (! $row->bucket) {
                    continue;
                }
                $yr = (string) $row->bucket;
                $buckets[$yr] = [
                    'label' => $yr,
                    'actionable' => (int) $row->actionable,
                    'watch' => (int) $row->watch,
                    'discarded' => (int) $row->discarded,
                ];
            }
        }

        return [
            'labels' => array_column($buckets, 'label'),
            'actionable' => array_column($buckets, 'actionable'),
            'watch' => array_column($buckets, 'watch'),
            'discarded' => array_column($buckets, 'discarded'),
        ];
    }
}


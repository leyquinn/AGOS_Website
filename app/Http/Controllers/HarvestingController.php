<?php

namespace App\Http\Controllers;

use App\Models\HarvestKeyword;
use App\Models\HarvestRun;
use App\Models\SocialPost;
use App\Models\VerifiedSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class HarvestingController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->query('per_page', 5);
        if (! in_array($perPage, [5, 10, 15, 25, 50])) {
            $perPage = 5;
        }

        $filter = (string) $request->query('filter', 'kept');
        if (! in_array($filter, ['actionable', 'watch', 'kept', 'removed', 'all'], true)) {
            $filter = 'kept';
        }

        $postsQuery = SocialPost::latest('scraped_at')->latest('id');
        if ($filter === 'actionable') {
            $postsQuery->where('model_status', 'VERIFIED_ACTIONABLE')
                ->orderByDesc('confidence_score');
        } elseif ($filter === 'watch') {
            $postsQuery->where('model_status', 'FLOOD_MENTION_NO_URGENCY')
                ->orderByDesc('p_rel');
        } elseif ($filter === 'kept') {
            $postsQuery->where('clean_status', 'kept');
        } elseif ($filter === 'removed') {
            $postsQuery->where('clean_status', 'removed');
        }

        $keywords = HarvestKeyword::orderByDesc('is_active')->orderBy('id')->get();
        $verifiedSources = VerifiedSource::orderByDesc('id')->get();
        $latestRun = HarvestRun::latest('id')->first();
        $socialPosts = $postsQuery->paginate($perPage)->withQueryString();

        $totalScrapedPosts = SocialPost::count();
        $keptPostsCount = SocialPost::where('clean_status', 'kept')->count();
        $removedPostsCount = SocialPost::where('clean_status', 'removed')->count();
        $removalBreakdown = SocialPost::where('clean_status', 'removed')
            ->selectRaw('COALESCE(removal_category, "Other noise") as category, count(*) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->pluck('total', 'category')
            ->all();

        $analyzedPostsCount = SocialPost::whereNotNull('analyzed_at')->count();
        $pendingCleanCount = SocialPost::where('clean_status', 'pending')->count();
        $pendingModelCount = SocialPost::where('clean_status', 'kept')
            ->where(function ($q) {
                $q->whereNull('analyzed_at')
                  ->orWhere('model_status', 'pending');
            })->count();

        $canRunCleaner = $pendingCleanCount > 0;
        $canRunModel = ($pendingCleanCount === 0) && ($pendingModelCount > 0);

        $currentPipelineStage = 1;
        if ($pendingCleanCount > 0) {
            $currentPipelineStage = 2;
        } elseif ($pendingModelCount > 0) {
            $currentPipelineStage = 3;
        }

        $actionablePostsCount = SocialPost::where('model_status', 'VERIFIED_ACTIONABLE')->count();
        $earlyWarningPostsCount = SocialPost::where('model_status', 'FLOOD_MENTION_NO_URGENCY')->count();
        $unlocalizedPostsCount = SocialPost::where('model_status', 'DISCARDED_UNLOCALIZED')->count();
        $avgConfidenceScore = (float) (
            SocialPost::where('model_status', 'VERIFIED_ACTIONABLE')->avg('confidence_score')
            ?? SocialPost::where('is_flood_related', true)->avg('confidence_score')
            ?? 0.0
        );

        $ckptPath = config('services.agos_scraper.model_checkpoint_path');
        $checkpointReady = ($ckptPath && is_file($ckptPath))
            || is_file('c:/Users/narp/Desktop/Coding Projects/XLM- RoBERTa/checkpoints/best_model.pt');
        $barangayList = array_keys(self::getBarangayCentroids());

        if ($request->ajax()) {
            return view('harvesting._posts', [
                'socialPosts' => $socialPosts,
                'totalScrapedPosts' => $totalScrapedPosts,
                'keptPostsCount' => $keptPostsCount,
                'removedPostsCount' => $removedPostsCount,
                'pendingCleanCount' => $pendingCleanCount,
                'pendingModelCount' => $pendingModelCount,
                'canRunCleaner' => $canRunCleaner,
                'canRunModel' => $canRunModel,
                'currentPipelineStage' => $currentPipelineStage,
                'actionablePostsCount' => $actionablePostsCount,
                'earlyWarningPostsCount' => $earlyWarningPostsCount,
                'barangayList' => $barangayList,
                'perPage' => $perPage,
                'filter' => $filter,
            ]);
        }

        return view('harvesting.index', [
            'keywords' => $keywords,
            'verifiedSources' => $verifiedSources,
            'latestRun' => $latestRun,
            'socialPosts' => $socialPosts,
            'totalScrapedPosts' => $totalScrapedPosts,
            'keptPostsCount' => $keptPostsCount,
            'removedPostsCount' => $removedPostsCount,
            'removalBreakdown' => $removalBreakdown,
            'analyzedPostsCount' => $analyzedPostsCount,
            'pendingCleanCount' => $pendingCleanCount,
            'pendingModelCount' => $pendingModelCount,
            'canRunCleaner' => $canRunCleaner,
            'canRunModel' => $canRunModel,
            'currentPipelineStage' => $currentPipelineStage,
            'actionablePostsCount' => $actionablePostsCount,
            'earlyWarningPostsCount' => $earlyWarningPostsCount,
            'unlocalizedPostsCount' => $unlocalizedPostsCount,
            'avgConfidenceScore' => $avgConfidenceScore,
            'checkpointReady' => $checkpointReady,
            'barangayList' => $barangayList,
            'perPage' => $perPage,
            'filter' => $filter,
        ]);
    }

    public static function getBarangayCentroids(): array
    {
        $path = storage_path('app/harvest/model/barangay_centroids.json');
        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded) && ! empty($decoded)) {
                return $decoded;
            }
        }

        return [
            'Talomo' => ['lat' => 7.0510, 'lng' => 125.5680, 'radius' => 0.003, 'river_basin' => 'Talomo River Basin'],
            'Buhangin' => ['lat' => 7.1125, 'lng' => 125.6200, 'radius' => 0.003, 'river_basin' => 'Davao River Basin'],
            'Tigatto' => ['lat' => 7.1292, 'lng' => 125.5950, 'radius' => 0.003, 'river_basin' => 'Davao River Basin'],
            'Matina Crossing' => ['lat' => 7.0583, 'lng' => 125.5819, 'radius' => 0.003, 'river_basin' => 'Matina Pangi River Basin'],
            'Matina Pangi' => ['lat' => 7.0714, 'lng' => 125.5661, 'radius' => 0.003, 'river_basin' => 'Matina Pangi River Basin'],
            'Ma-a' => ['lat' => 7.0872, 'lng' => 125.5861, 'radius' => 0.003, 'river_basin' => 'Davao River Basin'],
            'Poblacion' => ['lat' => 7.0660, 'lng' => 125.6055, 'radius' => 0.003, 'river_basin' => 'Davao River Basin'],
        ];
    }

    public function verifyPost(Request $request, SocialPost $post): RedirectResponse
    {
        $validated = $request->validate([
            'barangay' => ['required', 'string', 'max:120'],
        ]);

        $centroids = self::getBarangayCentroids();
        $selectedBarangay = trim($validated['barangay']);

        $meta = $centroids[$selectedBarangay] ?? null;
        if (! $meta) {
            foreach ($centroids as $name => $info) {
                if (mb_strtolower($name) === mb_strtolower($selectedBarangay)) {
                    $selectedBarangay = $name;
                    $meta = $info;
                    break;
                }
            }
        }

        $baseLat = (float) ($meta['lat'] ?? $post->latitude ?? 7.0660);
        $baseLng = (float) ($meta['lng'] ?? $post->longitude ?? 125.6055);
        $maxR = (float) ($meta['radius'] ?? 0.0015);
        $basin = (string) ($meta['river_basin'] ?? $post->river_basin ?? 'Davao City Watershed');

        $hash = md5((string) $post->post_id);
        $angle = (hexdec(substr($hash, 0, 4)) / 65535.0) * 2.0 * M_PI;
        $r = $maxR * 0.25 * (hexdec(substr($hash, 4, 4)) / 65535.0);
        $lat = round($baseLat + $r * sin($angle), 7);
        $lng = round($baseLng + $r * cos($angle), 7);

        $pRel = max((float) $post->p_rel, 0.85);
        $dClust = max((float) $post->d_clusters, 0.95);
        $cs = round((0.35 * $pRel) + (0.65 * $dClust), 4);

        $post->update([
            'is_flood_related' => true,
            'model_status' => 'VERIFIED_ACTIONABLE',
            'urgency_level' => 'HIGH',
            'primary_barangay' => $selectedBarangay,
            'river_basin' => $basin,
            'extracted_locations' => [$selectedBarangay],
            'detected_locations' => [$selectedBarangay],
            'latitude' => $lat,
            'longitude' => $lng,
            'p_rel' => $pRel,
            'd_clusters' => $dClust,
            'confidence_score' => $cs,
            'discard_reason' => 'verified_by_official',
            'analyzed_at' => now(),
        ]);

        \Illuminate\Support\Facades\Cache::forget('operations_heatmap_bundle');

        return back()->with(
            'status',
            "Post #{$post->id} tagged as flood-related for Barangay {$selectedBarangay} (CS ".number_format($cs, 2).") and added to the Flood Heatmap."
        );
    }

    public function dismissPost(SocialPost $post): RedirectResponse
    {
        $post->update([
            'is_flood_related' => false,
            'model_status' => 'DISCARDED_NOT_FLOOD',
            'discard_reason' => 'dismissed_by_official',
            'analyzed_at' => now(),
        ]);

        \Illuminate\Support\Facades\Cache::forget('operations_heatmap_bundle');

        return back()->with(
            'status',
            "Post #{$post->id} dismissed by Barangay Official review."
        );
    }

    public function runCleaner(Request $request): RedirectResponse
    {
        @set_time_limit(0);

        $pendingCleanCount = SocialPost::where('clean_status', 'pending')->count();
        if ($pendingCleanCount === 0) {
            return to_route('harvesting')->with(
                'error',
                'No new uncleaned posts found. Please run the Facebook Scraper first (Step 1).'
            );
        }

        $strict = $request->boolean('strict');
        Artisan::call('agos:clean', $strict ? ['--strict' => true] : []);

        $kept = SocialPost::where('clean_status', 'kept')->count();
        $removed = SocialPost::where('clean_status', 'removed')->count();

        return to_route('harvesting')->with(
            'status',
            "Step 2 Complete! Flood Cleaner finished: {$kept} genuine Davao flood posts kept, {$removed} noise/duplicate posts removed. Step 3 (XLM-RoBERTa Model) is now ready to run."
        );
    }

    public function runModel(Request $request): RedirectResponse
    {
        @set_time_limit(0);

        $pendingCleanCount = SocialPost::where('clean_status', 'pending')->count();
        if ($pendingCleanCount > 0) {
            return to_route('harvesting')->with(
                'error',
                "Please run Step 2 (Flood Cleaner) first: there are {$pendingCleanCount} uncleaned scraped posts awaiting cleaning."
            );
        }

        $pendingModelCount = SocialPost::where('clean_status', 'kept')
            ->where(function ($q) {
                $q->whereNull('analyzed_at')
                  ->orWhere('model_status', 'pending');
            })->count();

        if ($pendingModelCount === 0) {
            return to_route('harvesting')->with(
                'error',
                'No pending clean posts to analyze. Please run Step 1 (Facebook Scraper) to collect new posts.'
            );
        }

        $validated = $request->validate([
            'scope' => ['nullable', 'in:pending,unanalyzed,kept,all'],
            'threshold' => ['nullable', 'numeric', 'min:0.10', 'max:0.95'],
        ]);

        $scope = $validated['scope'] ?? 'pending';
        $threshold = (float) ($validated['threshold'] ?? 0.50);

        $exitCode = Artisan::call('agos:analyze', [
            '--scope' => $scope,
            '--threshold' => $threshold,
        ]);

        if ($exitCode !== 0) {
            $output = trim(Artisan::output());
            \Illuminate\Support\Facades\Log::error('XLM-RoBERTa model pipeline failed', ['output' => $output]);

            return to_route('harvesting')->with(
                'error',
                $output ?: 'XLM-RoBERTa model pipeline encountered an error. Check storage/logs/laravel.log for details.'
            );
        }

        $actionable = SocialPost::where('model_status', 'VERIFIED_ACTIONABLE')->count();
        $earlyWatch = SocialPost::where('model_status', 'FLOOD_MENTION_NO_URGENCY')->count();
        $avgCs = number_format((float) (SocialPost::where('model_status', 'VERIFIED_ACTIONABLE')->avg('confidence_score') ?? 0), 2);

        return to_route('harvesting')->with(
            'status',
            "Step 3 Complete! XLM-RoBERTa + K-Means++ finished: {$actionable} Verified Actionable alerts (Avg CS {$avgCs}), {$earlyWatch} Flood Watch (HITL) posts. Operations Heatmap updated!"
        );
    }

    public function storeKeyword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'keyword' => ['required', 'string', 'max:120'],
            'platform' => ['nullable', 'string', 'max:50'],
        ]);

        $keywordText = trim($validated['keyword']);
        $clean = ltrim($keywordText, '#');
        $matchesCount = SocialPost::where('search_query', $keywordText)
            ->orWhere('full_text', 'like', '%'.$clean.'%')
            ->count();

        HarvestKeyword::updateOrCreate(
            ['keyword' => $keywordText],
            [
                'platform' => $validated['platform'] ?? 'Facebook',
                'is_active' => true,
                'matches_count' => $matchesCount,
            ]
        );

        return to_route('harvesting')->with('status', "Keyword listener \"{$keywordText}\" added and enabled.");
    }

    public function toggleKeyword(HarvestKeyword $keyword): RedirectResponse
    {
        $keyword->update([
            'is_active' => ! $keyword->is_active,
        ]);

        $state = $keyword->is_active ? 'enabled' : 'paused';

        return to_route('harvesting')->with('status', "Listener \"{$keyword->keyword}\" {$state}.");
    }

    public function destroyKeyword(HarvestKeyword $keyword): RedirectResponse
    {
        $name = $keyword->keyword;
        $keyword->delete();

        return to_route('harvesting')->with('status', "Listener \"{$name}\" removed.");
    }

    public function storeSource(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'platform' => ['nullable', 'string', 'max:50'],
        ]);

        $sourceName = trim($validated['name']);

        VerifiedSource::updateOrCreate(
            ['name' => $sourceName],
            [
                'platform' => $validated['platform'] ?? 'Facebook',
                'verified_at' => now()->toDateString(),
            ]
        );

        return to_route('harvesting')->with('status', "Verified source \"{$sourceName}\" added to whitelist.");
    }

    public function destroySource(VerifiedSource $source): RedirectResponse
    {
        $name = $source->name;
        $source->delete();

        return to_route('harvesting')->with('status', "Verified source \"{$name}\" removed.");
    }

    public function runScraper(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'quick_query' => ['nullable', 'string', 'max:120'],
            'scrolls' => ['nullable', 'integer', 'min:1', 'max:100'],
            'browser_mode' => ['nullable', 'in:visible,headless'],
        ]);

        $quickQuery = trim((string) ($validated['quick_query'] ?? ''));
        if ($quickQuery !== '') {
            HarvestKeyword::updateOrCreate(
                ['keyword' => $quickQuery],
                ['platform' => 'Facebook', 'is_active' => true]
            );
            $queries = [$quickQuery];
        } else {
            $queries = HarvestKeyword::where('is_active', true)->pluck('keyword')->values()->all();
        }

        if (empty($queries)) {
            return to_route('harvesting')->with('error', 'Please enable or add at least one keyword listener before running the scraper.');
        }

        // Mark any stale running jobs as stopped
        HarvestRun::where('status', 'running')->update([
            'status' => 'stopped',
            'finished_at' => now(),
        ]);

        $run = HarvestRun::create([
            'status' => 'running',
            'queries_json' => $queries,
            'scrolls' => (int) ($validated['scrolls'] ?? 3),
            'headless' => ($validated['browser_mode'] ?? 'visible') === 'headless',
            'log_excerpt' => 'Initializing scraper process for queries: '.implode(', ', $queries).'...',
            'started_at' => now(),
        ]);

        $phpBin = PHP_BINARY ?: 'D:\\Xampp\\php\\php.exe';
        $artisan = base_path('artisan');

        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = sprintf('start /B "" "%s" "%s" agos:scrape --run-id=%d > NUL 2>&1', $phpBin, $artisan, $run->id);
            @pclose(@popen($cmd, 'r'));
        } else {
            $cmd = sprintf('"%s" "%s" agos:scrape --run-id=%d > /dev/null 2>&1 &', $phpBin, $artisan, $run->id);
            @exec($cmd);
        }

        return to_route('harvesting')->with('status', 'Scraper started for: '.implode(', ', $queries));
    }

    public function stopScraper(): RedirectResponse
    {
        HarvestRun::where('status', 'running')->update([
            'status' => 'stopped',
            'finished_at' => now(),
        ]);

        return to_route('harvesting')->with('status', 'Stop signal sent to active scraper run.');
    }

    public function status(): JsonResponse
    {
        $latestRun = HarvestRun::latest('id')->first();
        $totalScrapedPosts = SocialPost::count();
        $recentPosts = SocialPost::latest('scraped_at')->latest('id')->limit(10)->get();

        return response()->json([
            'run' => $latestRun ? [
                'id' => $latestRun->id,
                'status' => $latestRun->status,
                'queries' => $latestRun->queries_json ?? [],
                'scrolls' => $latestRun->scrolls,
                'posts_collected' => $latestRun->posts_collected,
                'new_posts_saved' => $latestRun->new_posts_saved,
                'log_excerpt' => $latestRun->log_excerpt,
                'error_message' => $latestRun->error_message,
                'started_at_human' => $latestRun->started_at?->diffForHumans(),
                'finished_at_human' => $latestRun->finished_at?->diffForHumans(),
            ] : null,
            'total_scraped_posts' => $totalScrapedPosts,
            'recent_posts_count' => $recentPosts->count(),
        ]);
    }
}


<?php

namespace App\Http\Controllers;

use App\Models\HarvestKeyword;
use App\Models\HarvestRun;
use App\Models\SocialPost;
use App\Models\VerifiedSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HarvestingController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->query('per_page', 5);
        if (! in_array($perPage, [5, 10, 15, 25, 50])) {
            $perPage = 5;
        }

        $keywords = HarvestKeyword::orderByDesc('is_active')->orderBy('id')->get();
        $verifiedSources = VerifiedSource::orderByDesc('id')->get();
        $latestRun = HarvestRun::latest('id')->first();
        $socialPosts = SocialPost::latest('scraped_at')->latest('id')->paginate($perPage)->withQueryString();
        $totalScrapedPosts = SocialPost::count();

        if ($request->ajax()) {
            return view('harvesting._posts', [
                'socialPosts' => $socialPosts,
                'totalScrapedPosts' => $totalScrapedPosts,
                'perPage' => $perPage,
            ]);
        }

        return view('harvesting.index', [
            'keywords' => $keywords,
            'verifiedSources' => $verifiedSources,
            'latestRun' => $latestRun,
            'socialPosts' => $socialPosts,
            'totalScrapedPosts' => $totalScrapedPosts,
            'perPage' => $perPage,
        ]);
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


<?php

namespace App\Console\Commands;

use App\Models\HarvestKeyword;
use App\Models\HarvestRun;
use App\Models\SocialPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class RunFloodCleaner extends Command
{
    protected $signature = 'agos:clean
                            {--run-id= : Optional HarvestRun ID to attach summary metrics to}
                            {--strict : Enable stricter rule filtering in davao_flood_cleaner.py}';

    protected $description = 'Run davao_flood_cleaner.py on scraped social media posts to remove noise and duplicates';

    public function handle(): int
    {
        $posts = SocialPost::orderBy('id')->get();
        if ($posts->isEmpty()) {
            $this->info('No scraped social posts in database to clean.');
            return self::SUCCESS;
        }

        $cleanerDir = storage_path('app/harvest/cleaner');
        File::ensureDirectoryExists($cleanerDir);

        $inputJsonPath = "{$cleanerDir}/input_posts.json";
        $payload = $posts->map(fn (SocialPost $p): array => [
            'post_id' => $p->post_id,
            'search_query' => $p->search_query ?? '',
            'post_url' => $p->post_url ?? '',
            'author' => $p->author ?? 'Unknown',
            'timestamp' => $p->raw_timestamp ?? '',
            'full_text' => $p->full_text,
            'detected_locations' => $p->detected_locations ?? [],
            'urgency_level' => $p->urgency_level ?? 'NORMAL',
            'media_urls' => $p->media_urls ?? [],
            'scraped_at' => $p->scraped_at?->toIso8601String() ?? '',
        ])->values()->all();

        File::put($inputJsonPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $pythonBin = config('services.agos_scraper.python_bin', 'python');
        $cleanerScript = config('services.agos_scraper.cleaner_script_path', base_path('intelligence/cleaner/davao_flood_cleaner.py'));

        $command = [
            $pythonBin,
            $cleanerScript,
            '--input', $inputJsonPath,
            '--output-dir', $cleanerDir,
        ];

        if ($this->option('strict')) {
            $command[] = '--strict';
        }

        $process = new Process($command, base_path(), [
            'PYTHONIOENCODING' => 'utf-8',
            'PYTHONUTF8' => '1',
            'SYSTEMROOT' => getenv('SYSTEMROOT') ?: 'C:\\Windows',
            'PATH' => getenv('PATH') ?: '',
        ], null, 120);

        $process->run();

        $resultsPath = "{$cleanerDir}/cleaning_results.json";
        if (! is_file($resultsPath)) {
            $this->error('Cleaner failed to generate cleaning_results.json: '.$process->getErrorOutput());
            return self::FAILURE;
        }

        $results = json_decode((string) file_get_contents($resultsPath), true);
        if (! is_array($results)) {
            $this->error('Unable to parse cleaning_results.json');
            return self::FAILURE;
        }

        $now = now();
        $keptCount = 0;
        $removedCount = 0;

        DB::transaction(function () use ($results, $now, &$keptCount, &$removedCount) {
            foreach ($results['audit_records'] ?? [] as $record) {
                $postId = $record['post_id'] ?? null;
                if (! $postId) {
                    continue;
                }

                $status = $record['clean_status'] ?? 'kept';
                if ($status === 'kept') {
                    $keptCount++;
                } else {
                    $removedCount++;
                }

                SocialPost::where('post_id', $postId)->update([
                    'cleaned_text' => $record['cleaned_text'] ?? null,
                    'clean_status' => $status,
                    'removal_category' => $record['removal_category'] ?? null,
                    'removal_reason' => $record['removal_reason'] ?? null,
                    'cleaned_at' => $now,
                ]);
            }
        });

        $summaryData = [
            'total_input' => $results['total_input'] ?? count($posts),
            'kept' => $keptCount,
            'removed_total' => $removedCount,
            'retention_rate' => $results['retention_rate'] ?? 0.0,
            'removed_by_layer_counts' => $results['removed_by_layer_counts'] ?? [],
            'cleaned_at' => $now->toIso8601String(),
        ];

        $runId = $this->option('run-id');
        $targetRun = $runId ? HarvestRun::find($runId) : HarvestRun::latest('id')->first();
        if ($targetRun) {
            $logAppend = "\n[Flood Cleaner] Input: {$summaryData['total_input']} | Kept: {$keptCount} | Removed: {$removedCount} ({$summaryData['retention_rate']}% retained)";
            $targetRun->update([
                'posts_kept' => $keptCount,
                'posts_removed' => $removedCount,
                'cleaning_summary' => $summaryData,
                'log_excerpt' => trim(($targetRun->log_excerpt ?? '').$logAppend),
            ]);
        }

        // Refresh keyword listener match counts to reflect clean kept posts
        foreach (HarvestKeyword::all() as $kw) {
            $clean = ltrim(trim($kw->keyword), '#');
            $count = SocialPost::where('clean_status', 'kept')
                ->where(function ($q) use ($kw, $clean) {
                    $q->where('search_query', $kw->keyword)
                        ->orWhere('full_text', 'like', '%'.$clean.'%');
                })
                ->count();
            $kw->update(['matches_count' => $count]);
        }

        $this->info("Cleaned {$summaryData['total_input']} posts: {$keptCount} kept, {$removedCount} removed as noise.");

        return self::SUCCESS;
    }
}


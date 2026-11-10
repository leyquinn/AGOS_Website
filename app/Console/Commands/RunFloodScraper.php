<?php

namespace App\Console\Commands;

use App\Models\HarvestKeyword;
use App\Models\HarvestRun;
use App\Models\SocialPost;
use App\Models\VerifiedSource;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Throwable;

class RunFloodScraper extends Command
{
    protected $signature = 'agos:scrape
                            {--run-id= : Existing HarvestRun ID to execute}
                            {--scrolls=3 : Number of scrolls per query}
                            {--headless : Run Chrome in headless mode}';

    protected $description = 'Run the Davao City Facebook flood scraper using active AGOS keyword listeners';

    public function handle(): int
    {
        $runId = $this->option('run-id');
        $run = $runId ? HarvestRun::find($runId) : null;

        if (! $run) {
            $queries = HarvestKeyword::where('is_active', true)->pluck('keyword')->values()->all();
            if (empty($queries)) {
                $this->error('No active keyword listeners configured.');
                return self::FAILURE;
            }

            $run = HarvestRun::create([
                'status' => 'running',
                'queries_json' => $queries,
                'scrolls' => max(1, (int) $this->option('scrolls')),
                'headless' => (bool) $this->option('headless'),
                'started_at' => now(),
            ]);
        } else {
            $run->update([
                'status' => 'running',
                'started_at' => $run->started_at ?? now(),
            ]);
        }

        $queries = $run->queries_json ?: HarvestKeyword::where('is_active', true)->pluck('keyword')->values()->all();
        if (empty($queries)) {
            $run->update([
                'status' => 'failed',
                'error_message' => 'No active search queries to scrape.',
                'finished_at' => now(),
            ]);
            return self::FAILURE;
        }

        $runDir = storage_path("app/harvest/runs/run_{$run->id}");
        File::ensureDirectoryExists($runDir);

        $queriesFile = "{$runDir}/queries.json";
        File::put($queriesFile, json_encode(array_values($queries), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $outputJsonPath = "{$runDir}/davao_fb_flood_reports.json";
        $run->update(['output_path' => $outputJsonPath]);

        $pythonBin = config('services.agos_scraper.python_bin', 'python');
        $scriptPath = config('services.agos_scraper.script_path', base_path('intelligence/scraper/davao_flood_scraper.py'));
        $profileDir = config('services.agos_scraper.profile_dir');

        $command = [
            $pythonBin,
            $scriptPath,
            '--queries-file', $queriesFile,
            '--output-dir', $runDir,
            '--scrolls', (string) max(1, $run->scrolls),
            '--scroll-delay', '2.5',
            '--block-images',
        ];

        if ($profileDir) {
            $command[] = '--profile-dir';
            $command[] = $profileDir;
        }

        if ($run->headless) {
            $command[] = '--headless';
        }

        $logBuffer = "Starting AGOS Facebook Scraper (Run #{$run->id})...\nQueries: ".implode(', ', $queries)."\n";
        $run->update(['log_excerpt' => $logBuffer]);

        try {
            $process = new Process($command, base_path(), [
                'PYTHONIOENCODING' => 'utf-8',
                'PYTHONUTF8' => '1',
                'SYSTEMROOT' => getenv('SYSTEMROOT') ?: 'C:\\Windows',
                'PATH' => getenv('PATH') ?: '',
                'TEMP' => getenv('TEMP') ?: sys_get_temp_dir(),
                'TMP' => getenv('TMP') ?: sys_get_temp_dir(),
                'USERPROFILE' => getenv('USERPROFILE') ?: '',
                'LOCALAPPDATA' => getenv('LOCALAPPDATA') ?: '',
                'APPDATA' => getenv('APPDATA') ?: '',
            ], null, 3600);

            $lastSyncTime = 0;

            $process->run(function (string $type, string $buffer) use ($run, &$logBuffer, $outputJsonPath, &$lastSyncTime): void {
                $logBuffer .= $buffer;
                if (strlen($logBuffer) > 12000) {
                    $logBuffer = substr($logBuffer, -12000);
                }

                // Check if user requested stop from UI
                $freshRun = HarvestRun::find($run->id);
                if ($freshRun && $freshRun->status === 'stopped') {
                    return;
                }

                $nowTs = time();
                if ($nowTs - $lastSyncTime >= 2) {
                    $lastSyncTime = $nowTs;
                    $this->syncJsonToDatabase($run, $outputJsonPath);
                    $run->update(['log_excerpt' => $logBuffer]);
                }
            });

            // Final sync of scraped JSON output
            $this->syncJsonToDatabase($run, $outputJsonPath);
            $this->call('agos:clean', ['--run-id' => $run->id]);

            $freshRun = HarvestRun::find($run->id);
            if ($freshRun && $freshRun->status === 'stopped') {
                $run->update([
                    'log_excerpt' => $logBuffer."\n[Stopped by user]",
                    'finished_at' => now(),
                ]);
                return self::SUCCESS;
            }

            if (! $process->isSuccessful() && $run->posts_collected === 0) {
                $run->update([
                    'status' => 'failed',
                    'log_excerpt' => $logBuffer,
                    'error_message' => 'Scraper exited with code '.$process->getExitCode().'. Check log excerpt for details.',
                    'finished_at' => now(),
                ]);
                return self::FAILURE;
            }

            $run->update([
                'status' => 'completed',
                'log_excerpt' => $logBuffer,
                'finished_at' => now(),
            ]);

            // Update last_scraped_at on active keywords
            HarvestKeyword::whereIn('keyword', $queries)->update(['last_scraped_at' => now()]);
            $this->refreshKeywordCounts();

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->syncJsonToDatabase($run, $outputJsonPath);
            $run->update([
                'status' => 'failed',
                'log_excerpt' => $logBuffer."\nException: ".$e->getMessage(),
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);
            return self::FAILURE;
        }
    }

    public function syncJsonToDatabase(HarvestRun $run, string $jsonPath): void
    {
        if (! is_file($jsonPath)) {
            return;
        }

        $raw = @file_get_contents($jsonPath);
        if (! $raw) {
            return;
        }

        $items = json_decode($raw, true);
        if (! is_array($items)) {
            return;
        }

        $whitelistedNames = VerifiedSource::pluck('name')
            ->map(fn (string $n): string => mb_strtolower(trim($n)))
            ->all();

        $newSaved = 0;

        foreach ($items as $item) {
            $postId = $item['post_id'] ?? null;
            $fullText = trim((string) ($item['full_text'] ?? ''));
            if (! $postId || $fullText === '') {
                continue;
            }

            $author = trim((string) ($item['author'] ?? 'Unknown'));
            $isWhitelisted = in_array(mb_strtolower($author), $whitelistedNames, true);

            $postedAt = null;
            if (! empty($item['iso_timestamp'])) {
                try {
                    $postedAt = Carbon::parse($item['iso_timestamp']);
                } catch (Throwable) {
                    $postedAt = null;
                }
            }

            $scrapedAt = null;
            if (! empty($item['scraped_at'])) {
                try {
                    $scrapedAt = Carbon::parse($item['scraped_at']);
                } catch (Throwable) {
                    $scrapedAt = now();
                }
            }

            $post = SocialPost::updateOrCreate(
                ['post_id' => $postId],
                [
                    'harvest_run_id' => $run->id,
                    'search_query' => $item['search_query'] ?? null,
                    'platform' => 'Facebook',
                    'author' => $author ?: 'Unknown',
                    'post_url' => $item['post_url'] ?? null,
                    'raw_timestamp' => $item['timestamp'] ?: ($item['raw_timestamp'] ?? null),
                    'posted_at' => $postedAt,
                    'full_text' => $fullText,
                    'detected_locations' => $item['detected_locations'] ?? [],
                    'urgency_level' => $item['urgency_level'] ?? 'NORMAL',
                    'media_urls' => $item['media_urls'] ?? [],
                    'is_whitelisted_source' => $isWhitelisted,
                    'scraped_at' => $scrapedAt ?? now(),
                ]
            );

            if ($post->wasRecentlyCreated) {
                $newSaved++;
            }
        }

        $run->update([
            'posts_collected' => count($items),
            'new_posts_saved' => $run->new_posts_saved + $newSaved,
        ]);

        if ($newSaved > 0) {
            $this->callSilent('agos:clean', ['--run-id' => $run->id]);
        }

        $this->refreshKeywordCounts();
    }

    private function refreshKeywordCounts(): void
    {
        $keywords = HarvestKeyword::all();
        foreach ($keywords as $kw) {
            $clean = ltrim(trim($kw->keyword), '#');
            $count = SocialPost::where('search_query', $kw->keyword)
                ->orWhere('full_text', 'like', '%'.$clean.'%')
                ->count();
            $kw->update(['matches_count' => $count]);
        }
    }
}


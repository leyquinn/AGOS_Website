<?php

namespace App\Console\Commands;

use App\Models\HarvestRun;
use App\Models\SocialPost;
use App\Models\VerifiedSource;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Throwable;

class ImportModelOutput extends Command
{
    protected $signature = 'agos:import-model-output
                            {--dir= : Directory containing Cleaned_verified_flood_reports_flood_reports.json and Cleaned_flood_mentioned_no_urgency_flood_reports.json}';

    protected $description = 'Import precomputed Cleaned XLM-RoBERTa Verified & Flood Watch (No Urgency) outputs into AGOS';

    public function handle(): int
    {
        $modelDir = storage_path('app/harvest/model');
        $sourceDir = $this->option('dir') ?: $modelDir;

        $importedJsonPath = "{$modelDir}/imported_cleaned_results.json";

        if (! is_file($importedJsonPath) || $this->option('dir')) {
            $pythonBin = config('services.agos_scraper.python_bin', 'python');
            $modelScript = config('services.agos_scraper.model_script_path', base_path('intelligence/model/agos_model_pipeline.py'));
            $geojsonPath = public_path('data/davao-barangays.geojson');

            $process = new Process([
                $pythonBin,
                $modelScript,
                '--import-cleaned-dir', $sourceDir,
                '--output-dir', $modelDir,
                '--geojson', $geojsonPath,
            ], base_path(), [
                'PYTHONIOENCODING' => 'utf-8',
                'PYTHONUTF8' => '1',
                'SYSTEMROOT' => getenv('SYSTEMROOT') ?: 'C:\\Windows',
                'PATH' => getenv('PATH') ?: '',
            ], null, 180);

            $process->run();

            if (! is_file($importedJsonPath)) {
                $this->error('Failed to process Cleaned model outputs: '.$process->getErrorOutput().' '.$process->getOutput());
                return self::FAILURE;
            }
        }

        $payload = json_decode((string) file_get_contents($importedJsonPath), true);
        if (! is_array($payload) || empty($payload['results'])) {
            $this->error('Invalid or empty imported_cleaned_results.json');
            return self::FAILURE;
        }

        $whitelistedNames = VerifiedSource::pluck('name')
            ->map(fn (string $n): string => mb_strtolower(trim($n)))
            ->all();

        $now = now();
        $rows = [];
        $actionableCount = 0;
        $earlyWatchCount = 0;

        foreach ($payload['results'] as $rec) {
            $postId = $rec['post_id'] ?? null;
            $rawText = trim((string) ($rec['raw_text'] ?? ''));
            if (! $postId || $rawText === '') {
                continue;
            }

            $author = mb_substr(trim((string) ($rec['author'] ?? 'Facebook User')), 0, 250);
            $isWhitelisted = in_array(mb_strtolower($author), $whitelistedNames, true);
            $status = (string) ($rec['verification_status'] ?? 'VERIFIED_ACTIONABLE');

            if ($status === 'VERIFIED_ACTIONABLE') {
                $actionableCount++;
            } elseif ($status === 'FLOOD_MENTION_NO_URGENCY') {
                $earlyWatchCount++;
            }

            $postedAt = null;
            if (! empty($rec['iso_timestamp'])) {
                try {
                    $postedAt = Carbon::parse($rec['iso_timestamp'])->toDateTimeString();
                } catch (Throwable) {
                    $postedAt = null;
                }
            }

            $cs = $isWhitelisted ? 1.0 : (float) ($rec['confidence_score'] ?? 0.85);
            $dClust = $isWhitelisted ? 1.0 : (float) ($rec['d_clusters'] ?? 0.85);
            $locsJson = json_encode($rec['extracted_locations'] ?? [], JSON_UNESCAPED_UNICODE);

            $rows[] = [
                'harvest_run_id' => null,
                'post_id' => $postId,
                'search_query' => 'Cleaned Dataset (With Timestamp)',
                'platform' => 'Facebook',
                'author' => $author ?: 'Facebook User',
                'post_url' => ! empty($rec['post_url']) ? mb_substr((string) $rec['post_url'], 0, 500) : null,
                'raw_timestamp' => ! empty($rec['timestamp']) ? mb_substr((string) $rec['timestamp'], 0, 120) : null,
                'posted_at' => $postedAt,
                'full_text' => mb_substr($rawText, 0, 8000),
                'cleaned_text' => mb_substr((string) ($rec['cleaned_text'] ?? $rawText), 0, 8000),
                'clean_status' => 'kept',
                'removal_category' => null,
                'removal_reason' => null,
                'detected_locations' => $locsJson,
                'urgency_level' => $status === 'VERIFIED_ACTIONABLE' ? 'HIGH' : 'NORMAL',
                'media_urls' => json_encode([]),
                'is_whitelisted_source' => $isWhitelisted,
                'scraped_at' => $postedAt ?? $now->toDateTimeString(),
                'cleaned_at' => $now->toDateTimeString(),
                'is_flood_related' => (bool) ($rec['is_flood_related'] ?? true),
                'p_rel' => (float) ($rec['p_rel'] ?? 0.85),
                'd_clusters' => $dClust,
                'confidence_score' => $cs,
                'extracted_locations' => $locsJson,
                'primary_barangay' => $rec['primary_barangay'] ?? 'Davao City',
                'river_basin' => $rec['river_basin'] ?? 'Davao City Watershed',
                'latitude' => $rec['latitude'] ?? null,
                'longitude' => $rec['longitude'] ?? null,
                'cluster_id' => $rec['cluster_id'] ?? 0,
                'cluster_size' => $rec['cluster_size'] ?? 1,
                'model_status' => $status,
                'discard_reason' => $rec['discard_reason'] ?? null,
                'analyzed_at' => $now->toDateTimeString(),
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ];
        }

        SocialPost::whereIn('search_query', ['Precomputed Model Output', 'Cleaned Dataset (With Timestamp)'])->delete();

        foreach (array_chunk($rows, 40) as $chunk) {
            DB::table('social_posts')->upsert(
                $chunk,
                ['post_id'],
                [
                    'cleaned_text',
                    'clean_status',
                    'detected_locations',
                    'urgency_level',
                    'is_whitelisted_source',
                    'cleaned_at',
                    'is_flood_related',
                    'p_rel',
                    'd_clusters',
                    'confidence_score',
                    'extracted_locations',
                    'primary_barangay',
                    'river_basin',
                    'latitude',
                    'longitude',
                    'cluster_id',
                    'cluster_size',
                    'model_status',
                    'discard_reason',
                    'analyzed_at',
                    'updated_at',
                ]
            );
        }

        $targetRun = HarvestRun::latest('id')->first();
        if ($targetRun) {
            $targetRun->update([
                'posts_analyzed' => SocialPost::whereNotNull('analyzed_at')->count(),
                'posts_actionable' => $actionableCount,
                'posts_early_warning' => $earlyWatchCount,
                'model_summary' => $payload['summary'] ?? [],
            ]);
        }

        $this->info("Imported ".count($rows)." Cleaned model posts ({$actionableCount} Verified Actionable, {$earlyWatchCount} Flood Watch / No Urgency).");

        return self::SUCCESS;
    }
}

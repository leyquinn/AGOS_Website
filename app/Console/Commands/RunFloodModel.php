<?php

namespace App\Console\Commands;

use App\Models\HarvestRun;
use App\Models\SocialPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class RunFloodModel extends Command
{
    protected $signature = 'agos:analyze
                            {--run-id= : Optional HarvestRun ID to attach model summary metrics to}
                            {--scope=pending : Which posts to analyze: pending (unanalyzed only), kept (cleaned only), or all (all scraped posts)}
                            {--threshold=0.50 : Relevance probability threshold P_rel for flood classification}';

    protected $description = 'Run Multi-Task XLM-RoBERTa + K-Means++ inference pipeline on scraped flood posts';

    public function handle(): int
    {
        @set_time_limit(0);
        $scope = strtolower((string) ($this->option('scope') ?: 'pending'));
        $threshold = max(0.10, min(0.95, (float) $this->option('threshold')));

        $query = SocialPost::orderBy('id');
        if ($scope === 'pending' || $scope === 'unanalyzed') {
            $query->where(function ($q) {
                $q->whereNull('analyzed_at')
                  ->orWhere('model_status', 'pending');
            });
        } elseif ($scope === 'kept') {
            $query->where('clean_status', 'kept');
        }

        $posts = $query->get();
        if ($posts->isEmpty()) {
            $this->info('No social posts matching scope ['.$scope.'] to analyze.');
            return self::SUCCESS;
        }

        $modelDir = storage_path('app/harvest/model');
        File::ensureDirectoryExists($modelDir);

        $resultsPath = "{$modelDir}/model_results.json";
        if (is_file($resultsPath)) {
            @unlink($resultsPath);
        }

        $inputJsonPath = "{$modelDir}/input_posts.json";
        $payload = $posts->map(fn (SocialPost $p): array => [
            'id' => $p->id,
            'post_id' => $p->post_id,
            'author' => $p->author ?? 'Unknown',
            'is_whitelisted_source' => (bool) $p->is_whitelisted_source,
            'full_text' => $p->full_text,
            'cleaned_text' => $p->cleaned_text ?: $p->full_text,
            'urgency_level' => $p->urgency_level ?? 'NORMAL',
            'latitude' => $p->latitude,
            'longitude' => $p->longitude,
        ])->values()->all();

        File::put($inputJsonPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $pythonBin = config('services.agos_scraper.python_bin', 'python');
        $modelScript = config('services.agos_scraper.model_script_path', base_path('intelligence/model/agos_model_pipeline.py'));
        $checkpointPath = config('services.agos_scraper.model_checkpoint_path');
        $geojsonPath = public_path('data/davao-barangays.geojson');

        $command = [
            $pythonBin,
            $modelScript,
            '--input', $inputJsonPath,
            '--output-dir', $modelDir,
            '--threshold', (string) $threshold,
        ];

        if ($checkpointPath && is_file($checkpointPath)) {
            $command[] = '--checkpoint';
            $command[] = $checkpointPath;
        }

        if (is_file($geojsonPath)) {
            $command[] = '--geojson';
            $command[] = $geojsonPath;
        }

        $process = new Process($command, base_path(), [
            'PYTHONIOENCODING' => 'utf-8',
            'PYTHONUTF8' => '1',
            'SYSTEMROOT' => getenv('SYSTEMROOT') ?: 'C:\\Windows',
            'PATH' => getenv('PATH') ?: '',
            'USERPROFILE' => getenv('USERPROFILE') ?: 'C:\\Users\\narp',
        ], null, 600);

        $process->run();

        if (! is_file($resultsPath) || ! $process->isSuccessful()) {
            $err = trim($process->getErrorOutput().' '.$process->getOutput());
            $this->error('XLM-RoBERTa pipeline failed: '.($err ?: 'Unknown error. Check python logs.'));
            return self::FAILURE;
        }

        $output = json_decode((string) file_get_contents($resultsPath), true);
        if (! is_array($output)) {
            $this->error('Unable to parse model_results.json');
            return self::FAILURE;
        }

        $now = now();
        $results = $output['results'] ?? [];
        $summary = $output['summary'] ?? [];

        DB::transaction(function () use ($results, $now, $scope) {
            foreach ($results as $rec) {
                $postId = $rec['post_id'] ?? null;
                if (! $postId) {
                    continue;
                }

                $extractedLocs = $rec['extracted_locations'] ?? [];
                $updateData = [
                    'is_flood_related' => (bool) ($rec['is_flood_related'] ?? false),
                    'p_rel' => $rec['p_rel'] ?? 0.0,
                    'd_clusters' => $rec['d_clusters'] ?? 0.0,
                    'confidence_score' => $rec['confidence_score'] ?? 0.0,
                    'extracted_locations' => $extractedLocs,
                    'primary_barangay' => $rec['primary_barangay'] ?? null,
                    'river_basin' => $rec['river_basin'] ?? null,
                    'latitude' => $rec['latitude'] ?? null,
                    'longitude' => $rec['longitude'] ?? null,
                    'cluster_id' => $rec['cluster_id'] ?? null,
                    'cluster_size' => $rec['cluster_size'] ?? 0,
                    'model_status' => $rec['verification_status'] ?? 'DISCARDED_NOT_FLOOD',
                    'discard_reason' => $rec['discard_reason'] ?? null,
                    'analyzed_at' => $now,
                ];

                if (! empty($extractedLocs)) {
                    $updateData['detected_locations'] = $extractedLocs;
                }

                SocialPost::where('post_id', $postId)->update($updateData);
            }

            if ($scope === 'kept') {
                SocialPost::where('clean_status', 'removed')
                    ->where(function ($q) {
                        $q->whereNull('analyzed_at')
                          ->orWhere('model_status', 'pending');
                    })
                    ->update([
                        'is_flood_related' => false,
                        'p_rel' => 0.0,
                        'd_clusters' => 0.0,
                        'confidence_score' => 0.0,
                        'model_status' => 'DISCARDED_NOT_FLOOD',
                        'discard_reason' => 'filtered_by_cleaner',
                        'analyzed_at' => $now,
                    ]);
            }
        });

        $summary['scope'] = $scope;
        $summary['analyzed_at'] = $now->toIso8601String();

        $runId = $this->option('run-id');
        $targetRun = $runId ? HarvestRun::find($runId) : HarvestRun::latest('id')->first();
        if ($targetRun) {
            $actionableCount = (int) ($summary['verified_actionable_count'] ?? 0);
            $earlyCount = (int) ($summary['early_warning_count'] ?? 0);
            $totalAnalyzed = (int) ($summary['total_analyzed'] ?? count($results));
            $avgCs = number_format((float) ($summary['avg_confidence_score'] ?? 0), 2);
            $logAppend = "\n[XLM-RoBERTa + K-Means++] Analyzed: {$totalAnalyzed} | Actionable: {$actionableCount} | Flood Watch: {$earlyCount} | Avg CS: {$avgCs}";

            $targetRun->update([
                'posts_analyzed' => $totalAnalyzed,
                'posts_actionable' => $actionableCount,
                'posts_early_warning' => $earlyCount,
                'model_summary' => $summary,
                'log_excerpt' => trim(($targetRun->log_excerpt ?? '').$logAppend),
            ]);
        }

        $this->info(trim($process->getOutput()));

        return self::SUCCESS;
    }
}


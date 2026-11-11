<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPost extends Model
{
    protected $fillable = [
        'harvest_run_id',
        'post_id',
        'search_query',
        'platform',
        'author',
        'post_url',
        'raw_timestamp',
        'posted_at',
        'full_text',
        'cleaned_text',
        'clean_status',
        'removal_category',
        'removal_reason',
        'detected_locations',
        'urgency_level',
        'media_urls',
        'is_whitelisted_source',
        'scraped_at',
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
    ];

    protected function casts(): array
    {
        return [
            'detected_locations' => 'array',
            'extracted_locations' => 'array',
            'media_urls' => 'array',
            'is_whitelisted_source' => 'boolean',
            'is_flood_related' => 'boolean',
            'p_rel' => 'float',
            'd_clusters' => 'float',
            'confidence_score' => 'float',
            'latitude' => 'float',
            'longitude' => 'float',
            'cluster_id' => 'integer',
            'cluster_size' => 'integer',
            'posted_at' => 'datetime',
            'scraped_at' => 'datetime',
            'cleaned_at' => 'datetime',
            'analyzed_at' => 'datetime',
        ];
    }

    public function harvestRun(): BelongsTo
    {
        return $this->belongsTo(HarvestRun::class);
    }
}


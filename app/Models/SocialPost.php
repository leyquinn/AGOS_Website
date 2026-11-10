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
        'detected_locations',
        'urgency_level',
        'media_urls',
        'is_whitelisted_source',
        'scraped_at',
    ];

    protected function casts(): array
    {
        return [
            'detected_locations' => 'array',
            'media_urls' => 'array',
            'is_whitelisted_source' => 'boolean',
            'posted_at' => 'datetime',
            'scraped_at' => 'datetime',
        ];
    }

    public function harvestRun(): BelongsTo
    {
        return $this->belongsTo(HarvestRun::class);
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HarvestRun extends Model
{
    protected $fillable = [
        'status',
        'queries_json',
        'scrolls',
        'headless',
        'posts_collected',
        'new_posts_saved',
        'posts_kept',
        'posts_removed',
        'cleaning_summary',
        'output_path',
        'log_excerpt',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'queries_json' => 'array',
            'scrolls' => 'integer',
            'headless' => 'boolean',
            'posts_collected' => 'integer',
            'new_posts_saved' => 'integer',
            'posts_kept' => 'integer',
            'posts_removed' => 'integer',
            'cleaning_summary' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }
}


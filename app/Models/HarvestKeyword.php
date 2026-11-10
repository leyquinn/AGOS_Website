<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HarvestKeyword extends Model
{
    protected $fillable = [
        'keyword',
        'platform',
        'is_active',
        'matches_count',
        'last_scraped_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'matches_count' => 'integer',
            'last_scraped_at' => 'datetime',
        ];
    }
}


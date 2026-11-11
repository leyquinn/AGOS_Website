<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerifiedSource extends Model
{
    protected $fillable = [
        'name',
        'platform',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'date',
        ];
    }
}


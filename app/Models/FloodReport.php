<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FloodReport extends Model
{
    protected $fillable = [
        'reporter_name',
        'reporter_contact',
        'location',
        'water_level',
        'description',
    ];
}

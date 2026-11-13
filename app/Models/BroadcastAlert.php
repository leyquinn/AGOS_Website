<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadcastAlert extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'barangay_id',
        'social_post_id',
        'title',
        'message',
        'severity',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * The user (admin) who issued this broadcast.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The target barangay (null if citywide).
     */
    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    /**
     * The associated social post, if any.
     */
    public function socialPost(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class);
    }

    /**
     * Scope for active broadcast alerts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for alerts visible to a specific barangay or citywide.
     */
    public function scopeForBarangay($query, ?int $barangayId)
    {
        return $query->where(function ($q) use ($barangayId) {
            $q->whereNull('barangay_id');
            if ($barangayId) {
                $q->orWhere('barangay_id', $barangayId);
            }
        });
    }
}


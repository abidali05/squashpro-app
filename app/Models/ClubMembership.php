<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubMembership extends Model
{
    use HasFactory;

    public const STATUS_APPROVED = 'approved';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_REMOVED = 'removed';

    protected $fillable = [
        'club_id',
        'player_id',
        'membership_number',
        'verification_mode',
        'status',
        'approved_at',
        'removed_at',
        'removal_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'removed_at' => 'datetime',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(User::class, 'club_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }
}

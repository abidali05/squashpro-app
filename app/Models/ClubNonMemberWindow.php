<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubNonMemberWindow extends Model
{
    protected $table = 'club_non_member_windows';

    protected $fillable = [
        'club_id',
        'day',
        'is_available',
        'from_time',
        'to_time',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(User::class, 'club_id');
    }
}

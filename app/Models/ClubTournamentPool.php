<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubTournamentPool extends Model
{
    protected $table = 'club_tournament_pools';

    protected $fillable = [
        'club_id',
        'tournament_id',
        'format',
        'has_pools',
        'pools',
    ];

    protected function casts(): array
    {
        return [
            'has_pools' => 'boolean',
            'pools' => 'array',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(User::class, 'club_id');
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class, 'tournament_id');
    }
}

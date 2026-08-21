<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentTeamPlayer extends Model
{
    protected $fillable = [
        'team_id',
        'player_id',
        'position',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class, 'team_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }
}

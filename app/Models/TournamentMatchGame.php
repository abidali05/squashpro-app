<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentMatchGame extends Model
{
    use HasFactory;

    protected $table = 'tournament_match_games';

    protected $fillable = [
        'match_id',
        'game_number',
        'home_score',
        'away_score',
        'winner_player_id',
        'starting_server_id',
        'starting_serving_side',
        'start_time',
        'end_time',
        'duration_seconds',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'home_score' => 'integer',
        'away_score' => 'integer',
        'duration_seconds' => 'integer',
        'game_number' => 'integer',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'match_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_player_id');
    }

    public function startingServer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'starting_server_id');
    }

    public function rallies(): HasMany
    {
        return $this->hasMany(TournamentMatchRally::class, 'game_id');
    }
}

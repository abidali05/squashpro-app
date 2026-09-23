<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'fixture_id',
        'sequence',
        'home_player_id',
        'away_player_id',
        'home_player_placeholder',
        'away_player_placeholder',
        'venue_id',
        'court_id',
        'start_date',
        'start_time',
        'status',
        'score',
        'winner_player_id',
        'best_of',
        'current_game',
        'toss_winner_player_id',
        'initial_server_player_id',
        'initial_serving_side',
        'current_server_id',
        'current_serving_side',
        'can_change_serving_side',
        'match_start_time',
        'match_end_time',
        'current_game_start_time',
    ];

    protected $casts = [
        'can_change_serving_side' => 'boolean',
        'match_start_time' => 'datetime',
        'match_end_time' => 'datetime',
        'current_game_start_time' => 'datetime',
        'best_of' => 'integer',
        'current_game' => 'integer',
    ];

    public function fixture(): BelongsTo
    {
        return $this->belongsTo(TournamentFixture::class, 'fixture_id');
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class, 'court_id');
    }

    public function homePlayer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'home_player_id');
    }

    public function awayPlayer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'away_player_id');
    }

    public function winnerPlayer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_player_id');
    }

    public function tossWinner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'toss_winner_player_id');
    }

    public function initialServer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initial_server_player_id');
    }

    public function currentServer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_server_id');
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(User::class, 'venue_id');
    }

    public function scorers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tournament_match_scorers', 'match_id', 'user_id')->withTimestamps();
    }

    public function umpires(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tournament_match_umpires', 'match_id', 'user_id')->withTimestamps();
    }

    public function games(): HasMany
    {
        return $this->hasMany(TournamentMatchGame::class, 'match_id');
    }

    public function rallies(): HasMany
    {
        return $this->hasMany(TournamentMatchRally::class, 'match_id');
    }
}

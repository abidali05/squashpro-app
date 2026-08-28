<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
}

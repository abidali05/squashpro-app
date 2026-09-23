<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentMatchRally extends Model
{
    use HasFactory;

    protected $table = 'tournament_match_rallies';

    protected $fillable = [
        'match_id',
        'game_id',
        'sequence',
        'server_player_id',
        'serving_side',
        'call_type',
        'striker_player_id',
        'awarded_to_player_id',
        'home_score_after',
        'away_score_after',
        'next_server_player_id',
        'next_serving_side',
        'can_change_serving_side',
        'event_time',
        'is_undone',
        'undone_at',
        'undone_by',
    ];

    protected $casts = [
        'event_time' => 'datetime',
        'undone_at' => 'datetime',
        'is_undone' => 'boolean',
        'can_change_serving_side' => 'boolean',
        'sequence' => 'integer',
        'home_score_after' => 'integer',
        'away_score_after' => 'integer',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'match_id');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(TournamentMatchGame::class, 'game_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(User::class, 'server_player_id');
    }

    public function striker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'striker_player_id');
    }

    public function awardedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'awarded_to_player_id');
    }

    public function nextServer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'next_server_player_id');
    }

    public function undoneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'undone_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'fixture_id',
        'sequence',
        'home_player_id',
        'away_player_id',
        'status',
        'score',
        'winner_player_id',
    ];

    public function fixture(): BelongsTo
    {
        return $this->belongsTo(TournamentFixture::class, 'fixture_id');
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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentFixture extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'group_id',
        'round',
        'home_club_id',
        'away_club_id',
        'is_bye',
        'bye_club_id',
        'status',
        'winner_club_id',
    ];

    protected $casts = [
        'is_bye' => 'boolean',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class, 'tournament_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TournamentGroup::class, 'group_id');
    }

    public function homeClub(): BelongsTo
    {
        return $this->belongsTo(User::class, 'home_club_id');
    }

    public function awayClub(): BelongsTo
    {
        return $this->belongsTo(User::class, 'away_club_id');
    }

    public function winnerClub(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_club_id');
    }

    public function byeClub(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bye_club_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'fixture_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    protected $fillable = [
        'club_id',
        'tournament_type',
        'opponent_club_id',
        'name',
        'format',
        'tournament_image',
        'gender',
        'player_level',
        'age_group',
        'start_date',
        'end_date',
        'registration_deadline',
        'entry_fees',
        'prize_pool',
        'allowed_player',
        'maximum_players',
        'registered_players_count',
        'status',
        'rules',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'registration_deadline' => 'datetime',
            'entry_fees' => 'decimal:2',
            'prize_pool' => 'decimal:2',
            'allowed_player' => 'integer',
            'maximum_players' => 'integer',
            'registered_players_count' => 'integer',
            'player_level' => 'array',
            'opponent_club_id' => 'integer',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tournament) {
            if (isset($tournament->maximum_players) && !$tournament->allowed_player) {
                $tournament->allowed_player = $tournament->maximum_players;
            }
        });
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(User::class, 'club_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(TournamentRegistration::class);
    }
}

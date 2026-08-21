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
        'created_by_admin',
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
            'opponent_club_id' => 'array',
            'created_by_admin' => 'boolean',
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

    public function invitations(): HasMany
    {
        return $this->hasMany(TournamentInvitation::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(TournamentTeam::class);
    }

    public function scorers()
    {
        return $this->belongsToMany(User::class, 'tournament_scorers', 'tournament_id', 'user_id')->withTimestamps();
    }

    public function umpires()
    {
        return $this->belongsToMany(User::class, 'tournament_umpires', 'tournament_id', 'user_id')->withTimestamps();
    }

    public function groups(): HasMany
    {
        return $this->hasMany(TournamentGroup::class, 'tournament_id');
    }

    public function fixtures(): HasMany
    {
        return $this->hasMany(TournamentFixture::class, 'tournament_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubTournamentRule extends Model
{
    protected $table = 'club_tournament_rules';

    protected $fillable = [
        'club_id',
        'tournament_id',
        'tournament_format',
        'competition_setup',
        'pool_rules',
        'knockout_rounds',
        'match_equipment',
        'scoring_rules',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'competition_setup' => 'array',
            'pool_rules' => 'array',
            'knockout_rounds' => 'array',
            'match_equipment' => 'array',
            'scoring_rules' => 'array',
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'name',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class, 'tournament_id');
    }

    public function clubs(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tournament_group_clubs', 'group_id', 'club_id')->withTimestamps();
    }

    public function fixtures(): HasMany
    {
        return $this->hasMany(TournamentFixture::class, 'group_id');
    }
}

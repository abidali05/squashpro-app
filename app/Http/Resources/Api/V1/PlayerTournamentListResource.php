<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\Concerns\ResolvesTournamentDisplayStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerTournamentListResource extends JsonResource
{
    use ResolvesTournamentDisplayStatus;

    public function toArray(Request $request): array
    {
        return [
            'tournament_id'      => $this->id,
            'tournament_name'    => $this->name,
            'club_name'          => $this->club?->club_name ?? $this->club?->name,
            'address'            => $this->club?->address,
            'start_date'         => $this->start_date?->toDateString(),
            'end_date'           => $this->end_date?->toDateString(),
            'entry_fee'          => $this->normalizeNumber($this->entry_fees),
            'registered_players' => ((int) $this->registered_players_count) . '/' . ((int) $this->allowed_player),
            'tournament_status'  => $this->resolveDisplayStatus(),
            'is_registered'      => (bool) $this->is_registered,
            
            // New fields
            'tournament_type'    => $this->tournament_type,
            'opponent_club_id'   => $this->opponent_club_id,
            'gender'             => $this->gender,
            'player_level'       => $this->player_level,
            'age_group'          => $this->age_group,
            'maximum_players'    => $this->maximum_players,
        ];
    }
}

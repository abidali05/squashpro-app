<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TournamentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $registered = (int) $this->registered_players_count;
        $allowed = (int) $this->allowed_player;
        $authId = $request->user()?->id;

        return [
            'id' => $this->id,
            'club_id' => $this->club_id,
            'opponent_club_id' => $this->opponent_club_id,
            'tournament_name' => $this->name,
            'tournament_type' => $this->tournament_type,
            'format' => $this->format,
            'tournament_image' => $this->resolveImageUrl($this->tournament_image),
            'gender' => $this->gender,
            'player_level' => $this->player_level,
            'age_group' => $this->age_group,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'registration_deadline' => $this->registration_deadline?->toDateString(),
            'entry_fees' => $this->normalizeNumber($this->entry_fees),
            'prize' => $this->normalizeNumber($this->prize_pool),
            'allowed_player' => $allowed,
            'maximum_players' => $this->maximum_players,
            'registered_players_count' => $registered,
            'players_count' => $registered.'/'.$allowed,
            'status' => $this->status,
            'rules' => $this->rules,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'is_creator' => $this->club_id === $authId,
            'is_opponent' => $this->opponent_club_id === $authId,
        ];
    }

    private function resolveImageUrl(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        return Storage::disk('public')->url($value);
    }

    private function normalizeNumber(mixed $value): int|float
    {
        $numeric = (float) $value;

        return $numeric == (int) $numeric ? (int) $numeric : $numeric;
    }
}

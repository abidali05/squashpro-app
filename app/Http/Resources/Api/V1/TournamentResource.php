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
            'player_level' => array_map(function ($lvl) {
                return strtolower($lvl) === 'advanced' ? 'professional' : $lvl;
            }, $this->player_level ?? []),
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
            'status' => (function() use ($authId) {
                // Determine if the authenticated user is an opponent
                $opponentIds = (array) ($this->opponent_club_id ?? []);
                $isOpponent = $authId && in_array((int)$authId, array_map('intval', $opponentIds), true);

                if ($isOpponent) {
                    $invitation = \App\Models\TournamentInvitation::where('tournament_id', $this->id)
                        ->where('invited_club_id', $authId)
                        ->first();
                    return $invitation ? $invitation->status : 'pending';
                }

                // If they are the host/creator:
                if ($authId && $this->club_id === (int)$authId) {
                    // If overall tournament status is 'soft_accepted', return 'pending' to avoid confusion!
                    if ($this->status === 'soft_accepted') {
                        return 'pending';
                    }
                }

                return $this->status;
            })(),
            'rules' => $this->rules,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'is_creator' => $this->club_id === $authId,
            'is_opponent' => in_array((int)$authId, (array)($this->opponent_club_id ?? []), true),
            'scorers' => $this->tournament_type === 'CLUB_TO_CLUB' ? $this->scorers->map(function ($u) {
                return [
                    'id' => $u->id,
                    'full_name' => $u->name,
                    'email' => $u->email,
                    'phone' => $u->phone,
                    'profile_image_url' => $u->profile_image ? (str_starts_with($u->profile_image, 'http') ? $u->profile_image : Storage::disk('public')->url($u->profile_image)) : null,
                ];
            })->all() : [],
            'umpires' => $this->tournament_type === 'CLUB_TO_CLUB' ? $this->umpires->map(function ($u) {
                return [
                    'id' => $u->id,
                    'full_name' => $u->name,
                    'email' => $u->email,
                    'phone' => $u->phone,
                    'profile_image_url' => $u->profile_image ? (str_starts_with($u->profile_image, 'http') ? $u->profile_image : Storage::disk('public')->url($u->profile_image)) : null,
                ];
            })->all() : [],
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

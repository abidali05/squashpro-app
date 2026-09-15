<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\Concerns\ResolvesTournamentDisplayStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PlayerTournamentDetailResource extends JsonResource
{
    use ResolvesTournamentDisplayStatus;

    public function toArray(Request $request): array
    {
        $registration = \App\Models\TournamentRegistration::where('tournament_id', $this->id)
            ->where('player_id', $request->user()?->id)
            ->first();

        return [
            'tournament_id'         => $this->id,
            'tournament_image'      => $this->imageUrl($this->tournament_image),
            'tournament_status'     => $this->resolveDisplayStatus(),
            'club_name'             => $this->club?->club_name ?? $this->club?->name,
            'tournament_name'       => $this->name,
            'address'               => $this->club?->address,
            'start_date'            => $this->start_date?->toDateString(),
            'end_date'              => $this->end_date?->toDateString(),
            'registration_deadline' => $this->registration_deadline?->toIso8601String(),
            'entry_fee'             => $this->normalizeNumber($this->entry_fees),
            'prize_pool'            => $this->normalizeNumber($this->prize_pool),
            'registered_players'    => ((int) $this->registered_players_count) . '/' . ((int) $this->allowed_player),
            'rules'                 => $this->rules,
            'is_registered'         => (bool) ($registration && $registration->registration_status === 'registered'),
            'team_status'           => (function () use ($request) {
                $user = $request->user();
                if (! $user) {
                    return 'not_submitted';
                }
                if ($user->role === 'player') {
                    $clubIds = \App\Models\ClubMembership::where('player_id', $user->id)
                        ->where('status', 'approved')
                        ->pluck('club_id')
                        ->toArray();
                    if (! empty($clubIds)) {
                        $hasSubmittedTeam = \App\Models\TournamentTeam::where('tournament_id', $this->id)
                            ->whereIn('club_id', $clubIds)
                            ->where(function ($q) {
                                $q->where('submission_status', 'submitted')
                                  ->orWhereHas('players');
                            })
                            ->exists();
                        if ($hasSubmittedTeam) {
                            return 'submitted';
                        }
                    }
                    return 'not_submitted';
                }
                $team = \App\Models\TournamentTeam::where('tournament_id', $this->id)
                    ->where('club_id', $user->id)
                    ->first();
                return ($team && ($team->submission_status === 'submitted' || $team->players()->count() > 0)) ? 'submitted' : 'not_submitted';
            })(),
            'is_team_submitted'     => (function () use ($request) {
                $user = $request->user();
                if (! $user) {
                    return false;
                }
                if ($user->role === 'player') {
                    $clubIds = \App\Models\ClubMembership::where('player_id', $user->id)
                        ->where('status', 'approved')
                        ->pluck('club_id')
                        ->toArray();
                    if (! empty($clubIds)) {
                        return \App\Models\TournamentTeam::where('tournament_id', $this->id)
                            ->whereIn('club_id', $clubIds)
                            ->where(function ($q) {
                                $q->where('submission_status', 'submitted')
                                  ->orWhereHas('players');
                            })
                            ->exists();
                    }
                    return false;
                }
                $team = \App\Models\TournamentTeam::where('tournament_id', $this->id)
                    ->where('club_id', $user->id)
                    ->first();
                return (bool) ($team && ($team->submission_status === 'submitted' || $team->players()->count() > 0));
            })(),
            
            // New fields
            'tournament_type'       => $this->tournament_type,
            'opponent_club_id'      => $this->opponent_club_id,
            'gender'                => $this->gender,
            'player_level'          => array_map(function ($lvl) {
                return strtolower($lvl) === 'advanced' ? 'professional' : $lvl;
            }, $this->player_level ?? []),
            'age_group'             => $this->age_group,
            'maximum_players'       => $this->maximum_players,
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

            // Registration details
            'registration'          => $registration ? [
                'id'                  => $registration->id,
                'registration_status' => $registration->registration_status,
                'payment_status'      => $registration->payment_status,
                'amount'              => $this->normalizeNumber($registration->amount),
                'currency'            => $registration->currency,
            ] : null,
        ];
    }

    private function imageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}

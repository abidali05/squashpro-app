<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ClubMembership;
use App\Models\TournamentTeam;
use App\Models\TournamentTeamPlayer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ClubTournamentPoolResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $poolsPayload = $this->pools ?? [];
        $formattedPools = [];

        foreach ($poolsPayload as $poolData) {
            $poolName = $poolData['pool_name'] ?? '';
            $clubIds = isset($poolData['club_ids']) && is_array($poolData['club_ids']) ? array_map('intval', $poolData['club_ids']) : [];
            $playerIds = isset($poolData['player_ids']) && is_array($poolData['player_ids']) ? array_map('intval', $poolData['player_ids']) : [];

            $teams = [];
            $drawPosition = 1;

            if (! empty($playerIds)) {
                foreach ($playerIds as $pid) {
                    $pUser = User::find($pid);

                    $name = $pUser?->name ?? "Player #{$pid}";
                    $img = $this->imageUrl($pUser?->profile_image);

                    $teams[] = [
                        'club_id' => $pid,
                        'club_name' => $name,
                        'club_logo' => $img,
                        'draw_position' => $drawPosition++,
                        'players' => [
                            [
                                'player_id' => $pid,
                                'name' => $name,
                                'profile_image' => $img,
                            ],
                        ],
                    ];
                }
            } elseif (! empty($clubIds)) {
                foreach ($clubIds as $clubId) {
                    $clubUser = User::find($clubId);

                    $clubName = $clubUser?->club_name ?? $clubUser?->name ?? "Club #{$clubId}";
                    $clubLogoUrl = $this->imageUrl($clubUser?->club_logo ?? $clubUser?->profile_image);

                    $teamRecord = TournamentTeam::where('tournament_id', $this->tournament_id)
                        ->where('club_id', $clubId)
                        ->first();

                    $playersList = [];

                    if ($teamRecord) {
                        $teamPlayers = TournamentTeamPlayer::where('team_id', $teamRecord->id)
                            ->with('player')
                            ->orderBy('position', 'asc')
                            ->get();

                        foreach ($teamPlayers as $tp) {
                            $pUser = $tp->player;
                            if ($pUser) {
                                $playersList[] = [
                                    'player_id' => $pUser->id,
                                    'name' => $pUser->name,
                                    'profile_image' => $this->imageUrl($pUser->profile_image),
                                ];
                            }
                        }
                    }

                    $teams[] = [
                        'club_id' => $clubId,
                        'club_name' => $clubName,
                        'club_logo' => $clubLogoUrl,
                        'draw_position' => $drawPosition++,
                        'players' => $playersList,
                    ];
                }
            }

            $formattedPools[] = [
                'pool_name' => $poolName,
                'teams' => $teams,
            ];
        }

        return [
            'tournament_id' => (int) $this->tournament_id,
            'pools' => $formattedPools,
        ];
    }

    private function imageUrl(?string $path): string
    {
        if (! $path) {
            return '';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}

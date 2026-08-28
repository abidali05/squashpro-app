<?php

namespace App\Http\Resources\Api\V1;

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
        $tournament = $this->tournament;
        $tournamentType = $tournament?->tournament_type ?? ($tournament?->opponent_club_id ? 'CLUB_TO_CLUB' : 'CLUB_MEMBERS_ONLY');
        $poolsPayload = $this->pools ?? [];
        $formattedPools = [];

        foreach ($poolsPayload as $index => $poolData) {
            $poolName = $poolData['pool_name'] ?? '';
            $poolIndex = isset($poolData['pool_index']) ? (int) $poolData['pool_index'] : $index;
            $clubIds = isset($poolData['club_ids']) && is_array($poolData['club_ids']) ? array_map('intval', $poolData['club_ids']) : [];
            $playerIds = isset($poolData['player_ids']) && is_array($poolData['player_ids']) ? array_map('intval', $poolData['player_ids']) : [];

            $poolItem = [
                'pool_name' => $poolName,
                'pool_index' => $poolIndex,
            ];

            if ($tournamentType === 'CLUB_MEMBERS_ONLY' || (! empty($playerIds) && empty($clubIds))) {
                $playersList = [];
                $drawPosition = 1;

                foreach ($playerIds as $pid) {
                    $pUser = User::find($pid);

                    $name = $pUser?->name ?? "Player #{$pid}";
                    $img = $this->imageUrl($pUser?->profile_image);

                    $playersList[] = [
                        'id' => (int) $pid,
                        'name' => $name,
                        'email' => $pUser?->email,
                        'phone' => $pUser?->phone,
                        'profile_image' => $img,
                        'draw_position' => $drawPosition++,
                    ];
                }

                $poolItem['players'] = $playersList;
            } else {
                $teams = [];
                $drawPosition = 1;

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
                        'club_id' => (int) $clubId,
                        'club_name' => $clubName,
                        'club_logo' => $clubLogoUrl,
                        'draw_position' => $drawPosition++,
                        'players' => $playersList,
                    ];
                }

                $poolItem['teams'] = $teams;
            }

            $formattedPools[] = $poolItem;
        }

        return [
            'tournament_id' => (int) $this->tournament_id,
            'tournament_type' => $tournamentType,
            'format' => $this->format ?? $tournament?->format ?? 'knockout',
            'has_pools' => (bool) $this->has_pools,
            'pools' => $formattedPools,
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

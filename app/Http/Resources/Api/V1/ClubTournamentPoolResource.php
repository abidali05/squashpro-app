<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ClubMembership;
use App\Models\TournamentTeam;
use App\Models\TournamentTeamPlayer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClubTournamentPoolResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tournament = $this->tournament;
        $poolsPayload = $this->pools ?? [];

        $totalTeams = 0;
        $formattedPools = [];

        foreach ($poolsPayload as $poolData) {
            $poolName = $poolData['pool_name'] ?? '';
            $poolIndex = (int) ($poolData['pool_index'] ?? 0);
            $clubIds = array_map('intval', (array) ($poolData['club_ids'] ?? []));

            $teams = [];
            $drawPosition = 1;

            foreach ($clubIds as $clubId) {
                $totalTeams++;
                $clubUser = User::find($clubId);

                $clubName = $clubUser?->club_name ?? $clubUser?->name ?? "Club #{$clubId}";
                $clubLogoPath = $clubUser?->club_logo ?? $clubUser?->profile_image;
                $clubLogoUrl = $this->imageUrl($clubLogoPath);

                // Fetch submitted team for this tournament and club
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
                            $membership = ClubMembership::where('club_id', $clubId)
                                ->where('player_id', $pUser->id)
                                ->where('status', 'approved')
                                ->first();

                            $playersList[] = [
                                'player_id' => $pUser->id,
                                'full_name' => $pUser->name,
                                'profile_image' => $this->imageUrl($pUser->profile_image),
                                'level' => $pUser->playing_level ?? $pUser->player_level ?? '',
                                'membership_number' => $membership?->membership_number ?? '',
                            ];
                        }
                    }
                }

                $teams[] = [
                    'draw_position' => $drawPosition++,
                    'club_id' => $clubId,
                    'club_name' => $clubName,
                    'club_logo' => $clubLogoUrl,
                    'player_count' => count($playersList),
                    'players' => $playersList,
                ];
            }

            $formattedPools[] = [
                'pool_name' => $poolName,
                'pool_index' => $poolIndex,
                'team_count' => count($teams),
                'teams' => $teams,
            ];
        }

        return [
            'tournament_id' => (int) $this->tournament_id,
            'tournament_name' => $tournament?->name ?? '',
            'format' => $this->format,
            'has_pools' => (bool) $this->has_pools,
            'total_pools' => count($formattedPools),
            'total_teams' => $totalTeams,
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

        return asset('storage/' . $path);
    }
}

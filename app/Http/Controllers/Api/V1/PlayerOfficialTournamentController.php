<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Services\ClubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlayerOfficialTournamentController extends Controller
{
    public function __construct(private readonly ClubService $clubService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'The date query parameter is required in YYYY-MM-DD format.',
                'error_code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors()
            ], 422);
        }

        $date = $request->query('date');
        $user = $request->user();

        $tournaments = Tournament::where(function ($query) use ($user) {
            $query->whereHas('scorers', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->orWhereHas('umpires', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        })
        ->whereDate('start_date', '<=', $date)
        ->whereDate('end_date', '>=', $date)
        ->with(['club:id,club_name,name,club_logo', 'scorers:id', 'umpires:id'])
        ->paginate($request->query('per_page', 10));

        $mapped = collect($tournaments->items())->map(function ($t) use ($user) {
            $roles = [];
            if ($t->scorers->contains($user->id)) {
                $roles[] = 'scorer';
            }
            if ($t->umpires->contains($user->id)) {
                $roles[] = 'umpire';
            }

            return [
                'id' => $t->id,
                'name' => $t->name,
                'tournament_type' => $t->tournament_type,
                'format' => $t->format,
                'status' => $t->status,
                'start_date' => $t->start_date?->toDateString() ?? $t->start_date,
                'end_date' => $t->end_date?->toDateString() ?? $t->end_date,
                'host_club' => [
                    'id' => $t->club_id,
                    'club_name' => $t->club?->club_name ?? $t->club?->name,
                    'club_logo' => app_image_url($t->club?->club_logo)
                ],
                'assigned_roles' => $roles
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Official tournaments fetched successfully.',
            'data' => $mapped,
            'meta' => [
                'current_page' => $tournaments->currentPage(),
                'last_page' => $tournaments->lastPage(),
                'per_page' => $tournaments->perPage(),
                'total' => $tournaments->total(),
            ]
        ]);
    }

    public function show(Request $request, string $tournamentId): JsonResponse
    {
        $tournament = Tournament::with(['club', 'scorers', 'umpires'])->find($tournamentId);
        if (!$tournament) {
            return response()->json([
                'success' => false,
                'message' => 'Tournament not found.',
                'error_code' => 'NOT_FOUND'
            ], 404);
        }

        $user = $request->user();
        $isScorer = $tournament->scorers->contains($user->id);
        $isUmpire = $tournament->umpires->contains($user->id);

        if (!$isScorer && !$isUmpire) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned as an official for this tournament.',
                'error_code' => 'ACCESS_DENIED'
            ], 403);
        }

        $roles = [];
        if ($isScorer) $roles[] = 'scorer';
        if ($isUmpire) $roles[] = 'umpire';

        $opponentClubIds = is_array($tournament->opponent_club_id) 
            ? $tournament->opponent_club_id 
            : json_decode($tournament->opponent_club_id ?? '[]', true);

        $participatingClubs = [];
        if ($tournament->club) {
            $participatingClubs[] = [
                'club_id' => $tournament->club->id,
                'club_name' => $tournament->club->club_name ?? $tournament->club->name,
                'club_logo' => app_image_url($tournament->club->club_logo),
                'role' => 'host'
            ];
        }

        if (!empty($opponentClubIds)) {
            $opponents = \App\Models\User::whereIn('id', $opponentClubIds)->get();
            foreach ($opponents as $opp) {
                $participatingClubs[] = [
                    'club_id' => $opp->id,
                    'club_name' => $opp->club_name ?? $opp->name,
                    'club_logo' => app_image_url($opp->club_logo),
                    'role' => 'invited'
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Tournament details fetched successfully.',
            'data' => [
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
                'tournament_type' => $tournament->tournament_type,
                'format' => $tournament->format,
                'status' => $tournament->status,
                'start_date' => $tournament->start_date?->toDateString() ?? $tournament->start_date,
                'end_date' => $tournament->end_date?->toDateString() ?? $tournament->end_date,
                'host_club' => [
                    'id' => $tournament->club_id,
                    'club_name' => $tournament->club?->club_name ?? $tournament->club?->name,
                    'club_logo' => app_image_url($tournament->club?->club_logo)
                ],
                'participating_clubs' => $participatingClubs,
                'scorers' => $tournament->scorers->map(function ($u) {
                    return [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'profile_image' => app_image_url($u->profile_image)
                    ];
                }),
                'umpires' => $tournament->umpires->map(function ($u) {
                    return [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'profile_image' => app_image_url($u->profile_image)
                    ];
                }),
                'auth_player_roles' => $roles,
                'rules' => $tournament->rules,
                'gender' => $tournament->gender,
                'player_level' => $tournament->player_level,
                'age_group' => $tournament->age_group,
                'maximum_players' => $tournament->maximum_players
            ]
        ]);
    }

    public function fixtures(Request $request, string $tournamentId): JsonResponse
    {
        $tournament = Tournament::with(['scorers', 'umpires'])->find($tournamentId);
        if (!$tournament) {
            return response()->json([
                'success' => false,
                'message' => 'Tournament not found.',
                'error_code' => 'NOT_FOUND'
            ], 404);
        }

        $user = $request->user();
        if (!$tournament->scorers->contains($user->id) && !$tournament->umpires->contains($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned as an official for this tournament.',
                'error_code' => 'ACCESS_DENIED'
            ], 403);
        }

        $playerId = $request->filled('player_id') ? (int) $request->input('player_id') : null;
        $fixturesData = $this->clubService->getFixtures($request->user(), $tournamentId, $playerId);

        return response()->json([
            'success' => true,
            'message' => 'Tournament fixtures retrieved successfully.',
            'data' => $fixturesData
        ]);
    }
}

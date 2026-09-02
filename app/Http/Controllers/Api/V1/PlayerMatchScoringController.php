<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TournamentMatch;
use App\Services\SquashMatchScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class PlayerMatchScoringController extends Controller
{
    public function __construct(
        private readonly SquashMatchScoringService $scoringService
    ) {}

    /**
     * Start match, record toss winner and initial server/box.
     * POST /api/v1/player/matches/{match_id}/start
     */
    public function start(Request $request, string $matchId): JsonResponse
    {
        $match = TournamentMatch::find($matchId);
        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found.',
                'error_code' => 'NOT_FOUND'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'toss_winner_player_id' => ['required', 'integer'],
            'initial_server_player_id' => ['required', 'integer'],
            'initial_serving_side' => ['required', 'string', 'in:L,R,l,r'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'error_code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $this->scoringService->startMatch(
                $match,
                (int) $request->input('toss_winner_player_id'),
                (int) $request->input('initial_server_player_id'),
                (string) $request->input('initial_serving_side')
            );

            return response()->json([
                'success' => true,
                'message' => 'Match started successfully',
                'data' => $data,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'START_MATCH_FAILED',
            ], 500);
        }
    }

    /**
     * Record a rally event.
     * POST /api/v1/player/matches/{match_id}/rally
     */
    public function rally(Request $request, string $matchId): JsonResponse
    {
        $match = TournamentMatch::find($matchId);
        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found.',
                'error_code' => 'NOT_FOUND'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'call_type' => ['required', 'string', 'in:ace,clean_winner,tin,stroke,no_let,let'],
            'awarded_to_player_id' => ['required', 'integer'],
            'striker_player_id' => ['nullable', 'integer'],
            'handout_chosen_side' => ['nullable', 'string', 'in:L,R,l,r'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'error_code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $this->scoringService->recordRally(
                $match,
                (string) $request->input('call_type'),
                (int) $request->input('awarded_to_player_id'),
                $request->filled('striker_player_id') ? (int) $request->input('striker_player_id') : null,
                $request->input('handout_chosen_side')
            );

            return response()->json([
                'success' => true,
                'message' => 'Rally recorded successfully',
                'data' => $data,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'RECORD_RALLY_FAILED',
            ], 500);
        }
    }

    /**
     * Undo last recorded rally.
     * POST /api/v1/player/matches/{match_id}/undo
     */
    public function undo(Request $request, string $matchId): JsonResponse
    {
        $match = TournamentMatch::find($matchId);
        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found.',
                'error_code' => 'NOT_FOUND'
            ], 404);
        }

        try {
            $data = $this->scoringService->undoLastRally($match, $request->user()?->id);

            return response()->json([
                'success' => true,
                'message' => 'Last rally undone successfully',
                'data' => $data,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'UNDO_RALLY_FAILED',
            ], 400);
        }
    }

    /**
     * Get current live match state.
     * GET /api/v1/player/matches/{match_id}/live
     */
    public function live(Request $request, string $matchId): JsonResponse
    {
        $match = TournamentMatch::find($matchId);
        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found.',
                'error_code' => 'NOT_FOUND'
            ], 404);
        }

        try {
            $data = $this->scoringService->getLiveMatchStatePayload($match);

            return response()->json([
                'success' => true,
                'message' => 'Live match state fetched successfully',
                'data' => $data,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'GET_LIVE_STATE_FAILED',
            ], 500);
        }
    }

    /**
     * Complete and finalize match.
     * POST /api/v1/player/matches/{match_id}/complete
     */
    public function complete(Request $request, string $matchId): JsonResponse
    {
        $match = TournamentMatch::find($matchId);
        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found.',
                'error_code' => 'NOT_FOUND'
            ], 404);
        }

        $winnerPlayerId = $request->filled('winner_player_id') ? (int) $request->input('winner_player_id') : null;

        try {
            $data = $this->scoringService->completeMatch($match, $winnerPlayerId);

            return response()->json([
                'success' => true,
                'message' => 'Match finalized and official results recorded successfully.',
                'data' => $data,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'MATCH_NOT_FINISHED',
            ], 400);
        }
    }
}

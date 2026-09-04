<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Player\RegisterTournamentRequest;
use App\Http\Resources\Api\V1\PlayerTournamentDetailResource;
use App\Http\Requests\Api\V1\Player\IndexTournamentsRequest;
use App\Http\Resources\Api\V1\PlayerTournamentListResource;
use App\Http\Resources\Api\V1\ClubTournamentPoolResource;
use App\Http\Resources\Api\V1\ClubTournamentRuleResource;
use App\Services\ClubService;
use App\Services\PlayerTournamentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerTournamentController extends Controller
{
    public function __construct(
        private readonly PlayerTournamentService $playerTournamentService,
        private readonly ClubService $clubService
    ) {
    }

    public function index(IndexTournamentsRequest $request): JsonResponse
    {
        $filter = $request->string('filter')->toString() ?: $request->string('status')->toString();
        $requestedLevel = $request->input('player_level') ?? $request->input('level');

        $payload = $this->playerTournamentService->tournaments(
            $request->user(),
            $filter ?: null,
            (int) $request->integer('page', 1),
            (int) $request->integer('limit', 10),
            $requestedLevel
        );

        return response()->json([
            'success' => true,
            'message' => 'Tournaments fetched successfully',
            'data' => PlayerTournamentListResource::collection(collect($payload['items'])),
            'pagination' => $payload['pagination'],
        ]);
    }

    public function show(Request $request, string $tournament_id): JsonResponse
    {
        $tournament = $this->playerTournamentService->detail($request->user(), (int) $tournament_id);

        return response()->json([
            'success' => true,
            'message' => 'Tournament detail fetched successfully',
            'data' => new PlayerTournamentDetailResource($tournament),
        ]);
    }

    public function paymentMethods(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Payment methods fetched successfully',
            'data' => $this->playerTournamentService->paymentMethods(),
        ]);
    }

    public function register(RegisterTournamentRequest $request): JsonResponse
    {
        $registration = $this->playerTournamentService->register(
            $request->user(),
            (int) $request->integer('tournament_id'),
            $request->string('payment_method_id')->toString()
        );

        return response()->json([
            'success' => true,
            'message' => 'Tournament registered successfully',
            'data' => [
                'registration_id' => $registration->id,
                'registration_status' => $registration->registration_status,
                'payment_status' => $registration->payment_status,
                'tournament_id' => $registration->tournament_id,
            ],
        ], 201);
    }

    public function respondToParticipation(\App\Http\Requests\Api\V1\Player\PlayerParticipationRequest $request, string $tournament_id): JsonResponse
    {
        $status = $this->playerTournamentService->respondToParticipation(
            $request->user(),
            (int) $tournament_id,
            $request->input('decision')
        );

        return response()->json([
            'success' => true,
            'message' => 'Participation response recorded successfully.',
            'data' => [
                'status' => $status,
            ],
        ]);
    }

    public function completePayment(Request $request, string $tournament_id): JsonResponse
    {
        $request->validate([
            'payment_method_id' => ['required', 'string', 'in:card,wallet,cash,jazzcash,easypaisa'],
        ]);

        $registration = $this->playerTournamentService->completePayment(
            $request->user(),
            (int) $tournament_id,
            $request->string('payment_method_id')->toString()
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment completed successfully. Enrollment confirmed.',
            'data' => [
                'registration_id' => $registration->id,
                'registration_status' => $registration->registration_status,
                'payment_status' => $registration->payment_status,
                'tournament_id' => $registration->tournament_id,
            ],
        ]);
    }

    public function rules(Request $request, string $tournament_id): JsonResponse
    {
        $rules = $this->clubService->getTournamentRules($request->user(), $tournament_id);

        return response()->json([
            'success' => true,
            'message' => 'Tournament rules retrieved successfully',
            'data' => $rules ? new ClubTournamentRuleResource($rules) : null,
        ]);
    }

    public function pools(Request $request, string $tournament_id): JsonResponse
    {
        $pools = $this->clubService->getTournamentPools($request->user(), $tournament_id);

        return response()->json([
            'success' => true,
            'message' => 'Tournament pools retrieved successfully.',
            'data' => $pools ? new ClubTournamentPoolResource($pools) : null,
        ]);
    }

    public function fixtures(Request $request, string $tournament_id): JsonResponse
    {
        $playerId = $request->filled('player_id') ? (int) $request->input('player_id') : null;
        $matchStartDate = $request->input('match_start_date') 
            ?? $request->input('start_date') 
            ?? $request->input('date');

        if ($matchStartDate) {
            try {
                $matchStartDate = \Carbon\Carbon::parse($matchStartDate)->format('Y-m-d');
            } catch (\Throwable $e) {
                $matchStartDate = null;
            }
        }

        $data = $this->clubService->getFixtures($request->user(), $tournament_id, $playerId, null, $matchStartDate);

        return response()->json([
            'success' => true,
            'message' => 'Tournament fixtures retrieved successfully.',
            'data' => $data,
        ], 200);
    }
}

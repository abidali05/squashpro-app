<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Player\ClubCourtsRequest;
use App\Http\Requests\Api\V1\Player\IndexClubsRequest;
use App\Http\Requests\Api\V1\Player\TimeSlotsRequest;
use App\Http\Resources\Api\V1\PlayerClubDetailResource;
use App\Http\Resources\Api\V1\PlayerClubListResource;
use App\Http\Resources\Api\V1\PlayerCourtResource;
use App\Http\Resources\Api\V1\PlayerTimeSlotResource;
use App\Http\Requests\Api\V1\Player\GetPlayerClubsRequest;
use App\Http\Requests\Api\V1\Player\AddPlayerClubRequest;
use App\Http\Requests\Api\V1\Player\RemovePlayerClubRequest;
use App\Http\Resources\Api\V1\PlayerJoinedClubResource;
use App\Models\ClubMembership;
use App\Models\ClubMembershipRequest;
use App\Models\User;
use App\Notifications\Club\NewMembershipRequestNotification;
use App\Notifications\Club\PlayerLeftClubNotification;
use App\Support\ApiErrorCode;
use App\Support\AuditLogger;
use App\Services\PlayerBookingService;
use App\Services\PlayerMembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlayerClubController extends Controller
{
    public function __construct(
        private readonly PlayerBookingService $playerBookingService,
        private readonly PlayerMembershipService $playerMembershipService
    ) {
    }

    public function addPlayerClub(AddPlayerClubRequest $request): JsonResponse
    {
        $player = $request->user();
        $clubId = (int) $request->input('club_id');
        $membershipNumber = $request->input('membership_number');

        // Check if player has an approved membership already
        $hasApproved = ClubMembership::where('club_id', $clubId)
            ->where('player_id', $player->id)
            ->where('status', ClubMembership::STATUS_APPROVED)
            ->exists();

        // Check if player has a pending request already
        $hasPending = ClubMembershipRequest::where('club_id', $clubId)
            ->where('player_id', $player->id)
            ->where('status', ClubMembershipRequest::STATUS_PENDING)
            ->exists();

        if ($hasApproved || $hasPending) {
            return response()->json([
                'success' => false,
                'message' => 'Membership already exists or request already pending',
                'error_code' => ApiErrorCode::MEMBERSHIP_ALREADY_EXISTS,
                'errors' => new \stdClass(),
            ], 409);
        }

        $club = User::find($clubId);

        DB::beginTransaction();
        try {
            $membershipRequest = ClubMembershipRequest::updateOrCreate(
                [
                    'club_id' => $clubId,
                    'player_id' => $player->id,
                ],
                [
                    'membership_number' => $membershipNumber,
                    'status' => ClubMembershipRequest::STATUS_PENDING,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'rejection_reason' => null,
                ]
            );

            // Audit log
            AuditLogger::log(
                actorId: $player->id,
                action: 'request_membership',
                entityType: ClubMembershipRequest::class,
                entityId: $membershipRequest->id,
                before: null,
                after: $membershipRequest->toArray()
            );

            // Notify club
            $club->notify(new NewMembershipRequestNotification($player, $membershipNumber));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json([
            'success' => true,
            'status' => 201,
            'message' => 'Club membership request submitted successfully',
            'data' => [
                'club_id' => $clubId,
                'membership_number' => $membershipNumber,
                'membership_status' => 'pending',
            ],
        ], 201);
    }

    public function removePlayerClub(RemovePlayerClubRequest $request): JsonResponse
    {
        $player = $request->user();
        $clubId = (int) $request->input('club_id');
        $reason = trim($request->input('reason'));

        // Retrieve approved membership (guaranteed to exist due to validation)
        $membership = ClubMembership::where('club_id', $clubId)
            ->where('player_id', $player->id)
            ->where('status', ClubMembership::STATUS_APPROVED)
            ->first();

        $before = $membership->toArray();
        $club = User::find($clubId);

        DB::beginTransaction();
        try {
            $membership->update([
                'status' => ClubMembership::STATUS_REMOVED,
                'removed_at' => now(),
                'removal_reason' => $reason,
            ]);

            // Audit log
            AuditLogger::log(
                actorId: $player->id,
                action: 'remove_membership',
                entityType: ClubMembership::class,
                entityId: $membership->id,
                before: $before,
                after: $membership->toArray()
            );

            // Notify club
            $club->notify(new PlayerLeftClubNotification($player, $reason));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'Club membership removed successfully',
            'data' => [
                'club_id' => $clubId,
                'membership_status' => 'removed',
                'reason' => $reason,
            ],
        ], 200);
    }

    public function getPlayerClubs(GetPlayerClubsRequest $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        if ($perPage > 50) {
            $perPage = 50;
        }

        $paginator = $this->playerMembershipService->getPlayerClubs(
            $request->user(),
            $request->input('search'),
            $perPage
        );

        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'Player clubs retrieved successfully',
            'data' => PlayerJoinedClubResource::collection($paginator->items()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function index(IndexClubsRequest $request): JsonResponse
    {
        $result = $this->playerBookingService->clubs(
            $request->user(),
            $request->boolean('lowest_price'),
            $request->boolean('open_now'),
            $request->integer('page', 1),
            $request->integer('limit', 10)
        );

        return response()->json([
            'success' => true,
            'message' => 'Clubs fetched successfully',
            'data' => PlayerClubListResource::collection(collect($result['items'])),
            'pagination' => $result['pagination'],
        ]);
    }

    public function show(Request $request, string $club_id): JsonResponse
    {
        $club = $this->playerBookingService->clubDetails($request->user(), (int) $club_id);

        return response()->json([
            'success' => true,
            'message' => 'Club details fetched successfully',
            'data' => new PlayerClubDetailResource($club),
        ]);
    }

    public function courts(ClubCourtsRequest $request, string $club_id): JsonResponse
    {
        $dateStr = $request->string('date')->toString();
        $courts = $this->playerBookingService->clubCourts((int) $club_id, $dateStr);

        $club = User::findOrFail((int) $club_id);
        $day = strtolower(\Carbon\Carbon::parse($dateStr)->format('l'));
        $win = \App\Models\ClubNonMemberWindow::where('club_id', $club->id)
            ->where('day', $day)
            ->first();

        $fromTime = $club->non_member_booking_allowed ? ($win ? ($win->from_time ? substr((string)$win->from_time, 0, 5) : null) : ($club->non_member_booking_start_time ? substr((string)$club->non_member_booking_start_time, 0, 5) : null)) : null;
        $toTime = $club->non_member_booking_allowed ? ($win ? ($win->to_time ? substr((string)$win->to_time, 0, 5) : null) : ($club->non_member_booking_end_time ? substr((string)$club->non_member_booking_end_time, 0, 5) : null)) : null;

        $nonMemberBooking = [
            'allow_non_member_booking' => (bool) $club->non_member_booking_allowed,
            'day' => $day,
            'is_available' => $club->non_member_booking_allowed ? ($win ? (bool)$win->is_available : true) : false,
            'from_time' => $fromTime,
            'to_time' => $toTime,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Courts fetched successfully',
            'club_non_member_booking' => $nonMemberBooking,
            'data' => PlayerCourtResource::collection(collect($courts)),
        ]);
    }

    public function timeSlots(TimeSlotsRequest $request, string $court_id): JsonResponse
    {
        $payload = $this->playerBookingService->timeSlots(
            (int) $request->integer('club_id'),
            (int) $court_id,
            $request->string('date')->toString()
        );

        return response()->json([
            'success' => true,
            'message' => 'Time slots fetched successfully',
            'data' => [
                'club_id' => $payload['club_id'],
                'court_id' => $payload['court_id'],
                'date' => $payload['date'],
                'slots' => PlayerTimeSlotResource::collection(collect($payload['slots'])),
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ClubMembershipRequestResource;
use App\Http\Resources\Api\V1\ClubMembershipResource;
use App\Models\ClubMembership;
use App\Models\ClubMembershipRequest;
use App\Notifications\Club\MembershipApprovedNotification;
use App\Notifications\Club\MembershipRejectedNotification;
use App\Support\ApiErrorCode;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ClubMembershipController extends Controller
{
    public function indexRequests(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'in:created_at,-created_at'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'error_code' => ApiErrorCode::VALIDATION_ERROR,
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $club = $request->user();
        $status = $request->input('status', 'pending');
        $perPage = (int) $request->input('per_page', 20);

        $query = ClubMembershipRequest::query()
            ->where('club_id', $club->id)
            ->where('status', $status);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('membership_number', 'like', "%{$search}%")
                  ->orWhereHas('player', function ($sub) use ($search) {
                      $sub->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $sort = $request->input('sort');
        if ($sort) {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');
            $query->orderBy($field, $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Membership requests retrieved successfully.',
            'data' => ClubMembershipRequestResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function approveRequest(Request $request, string $id): JsonResponse
    {
        $club = $request->user();
        $membershipRequest = ClubMembershipRequest::where('id', $id)
            ->where('club_id', $club->id)
            ->first();

        if (! $membershipRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.',
                'error_code' => ApiErrorCode::RECORD_NOT_FOUND,
                'errors' => new \stdClass(),
            ], 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'membership_type' => ['nullable', 'string', 'in:temporary,permanent'],
            'membership_expiry_date' => [
                \Illuminate\Validation\Rule::requiredIf(fn () => $request->input('membership_type') === 'temporary'),
                'nullable',
                'date',
                'after:today',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'error_code' => ApiErrorCode::VALIDATION_ERROR,
                'errors' => $validator->errors(),
            ], 422);
        }

        $membershipType = $request->input('membership_type', 'temporary');
        $expiryDate = $membershipType === 'temporary' && $request->filled('membership_expiry_date')
            ? \Illuminate\Support\Carbon::parse($request->input('membership_expiry_date'))
            : null;

        // Idempotency check
        if ($membershipRequest->status === ClubMembershipRequest::STATUS_APPROVED) {
            $existingMembership = ClubMembership::where('club_id', $club->id)
                ->where('player_id', $membershipRequest->player_id)
                ->first();

            if ($existingMembership) {
                return response()->json([
                    'success' => true,
                    'message' => 'Membership approved successfully.',
                    'data' => new ClubMembershipResource($existingMembership),
                ]);
            }
        }

        DB::beginTransaction();
        try {
            $membershipRequest->update([
                'status' => ClubMembershipRequest::STATUS_APPROVED,
                'reviewed_by' => $club->id,
                'reviewed_at' => now(),
            ]);

            // Add player to the club members list
            $membership = ClubMembership::updateOrCreate(
                [
                    'club_id' => $club->id,
                    'player_id' => $membershipRequest->player_id,
                ],
                [
                    'membership_number' => $membershipRequest->membership_number,
                    'status' => ClubMembership::STATUS_APPROVED,
                    'approved_at' => now(),
                    'membership_type' => $membershipType,
                    'membership_expiry_date' => $expiryDate,
                ]
            );

            // Audit
            AuditLogger::log(
                actorId: $club->id,
                action: 'approve_member',
                entityType: ClubMembership::class,
                entityId: $membership->id,
                before: null,
                after: $membership->toArray()
            );

            // Notify player
            $player = $membershipRequest->player;
            if ($player) {
                $player->notify(new MembershipApprovedNotification($club, $membership->membership_number));
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json([
            'success' => true,
            'message' => 'Membership approved successfully.',
            'data' => new ClubMembershipResource($membership),
        ]);
    }

    public function rejectRequest(Request $request, string $id): JsonResponse
    {
        $club = $request->user();
        $membershipRequest = ClubMembershipRequest::where('id', $id)
            ->where('club_id', $club->id)
            ->first();

        if (! $membershipRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.',
                'error_code' => ApiErrorCode::RECORD_NOT_FOUND,
                'errors' => new \stdClass(),
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'error_code' => ApiErrorCode::VALIDATION_ERROR,
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $reason = $request->input('reason');
        $before = $membershipRequest->toArray();

        DB::beginTransaction();
        try {
            $membershipRequest->update([
                'status' => ClubMembershipRequest::STATUS_REJECTED,
                'reviewed_by' => $club->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            // Audit
            AuditLogger::log(
                actorId: $club->id,
                action: 'reject_member',
                entityType: ClubMembershipRequest::class,
                entityId: $membershipRequest->id,
                before: $before,
                after: $membershipRequest->toArray()
            );

            // If an active membership exists, update status to removed
            ClubMembership::where('club_id', $club->id)
                ->where('player_id', $membershipRequest->player_id)
                ->update([
                    'status' => ClubMembership::STATUS_REMOVED,
                    'removed_at' => now(),
                ]);

            // Notify player
            $player = $membershipRequest->player;
            if ($player) {
                $player->notify(new MembershipRejectedNotification($club, $reason));
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json([
            'success' => true,
            'message' => 'Membership rejected successfully.',
            'data' => new \stdClass(),
        ]);
    }

    public function indexMembers(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'in:name,-name,created_at,-created_at'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'error_code' => ApiErrorCode::VALIDATION_ERROR,
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $club = $request->user();
        $status = $request->input('status', 'approved');
        $perPage = (int) $request->input('per_page', 20);

        $query = ClubMembership::query()
            ->where('club_id', $club->id)
            ->where('status', $status);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('membership_number', 'like', "%{$search}%")
                  ->orWhereHas('player', function ($sub) use ($search) {
                      $sub->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $sort = $request->input('sort');
        if ($sort) {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');
            if ($field === 'name') {
                $query->join('users', 'club_memberships.player_id', '=', 'users.id')
                    ->select('club_memberships.*')
                    ->orderBy('users.name', $direction);
            } else {
                $query->orderBy('club_memberships.' . $field, $direction);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Members retrieved successfully.',
            'data' => ClubMembershipResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function addMember(Request $request): JsonResponse
    {
        $club = $request->user();

        $validator = Validator::make($request->all(), [
            'player_id' => [
                'required',
                'integer',
                \Illuminate\Validation\Rule::exists('users', 'id')->where(function ($q) {
                    $q->where('role', 'player');
                }),
            ],
            'membership_number' => ['required', 'string', 'max:255'],
            'verification_mode' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'error_code' => ApiErrorCode::VALIDATION_ERROR,
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $playerId = (int) $request->input('player_id');
        $membershipNumber = $request->input('membership_number');
        $verificationMode = $request->input('verification_mode');

        $exists = ClubMembership::where('club_id', $club->id)
            ->where('player_id', $playerId)
            ->where('status', ClubMembership::STATUS_APPROVED)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Duplicate active membership detected.',
                'error_code' => ApiErrorCode::MEMBERSHIP_ALREADY_EXISTS,
                'errors' => new \stdClass(),
            ], 409);
        }

        DB::beginTransaction();
        try {
            $membership = ClubMembership::updateOrCreate(
                [
                    'club_id' => $club->id,
                    'player_id' => $playerId,
                ],
                [
                    'membership_number' => $membershipNumber,
                    'verification_mode' => $verificationMode,
                    'status' => ClubMembership::STATUS_APPROVED,
                    'approved_at' => now(),
                ]
            );

            AuditLogger::log(
                actorId: $club->id,
                action: 'add_member',
                entityType: ClubMembership::class,
                entityId: $membership->id,
                before: null,
                after: $membership->toArray()
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json([
            'success' => true,
            'message' => 'Player added to club successfully.',
            'data' => new ClubMembershipResource($membership),
        ], 201);
    }

    public function removeMember(Request $request, string $id): JsonResponse
    {
        $club = $request->user();
        $membership = ClubMembership::where('id', $id)
            ->where('club_id', $club->id)
            ->first();

        if (! $membership) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.',
                'error_code' => ApiErrorCode::RECORD_NOT_FOUND,
                'errors' => new \stdClass(),
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'error_code' => ApiErrorCode::VALIDATION_ERROR,
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $before = $membership->toArray();

        DB::beginTransaction();
        try {
            $membership->update([
                'status' => ClubMembership::STATUS_REMOVED,
                'removed_at' => now(),
            ]);

            AuditLogger::log(
                actorId: $club->id,
                action: 'remove_member',
                entityType: ClubMembership::class,
                entityId: $membership->id,
                before: $before,
                after: $membership->toArray()
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json([
            'success' => true,
            'message' => 'Member removed successfully.',
            'data' => new \stdClass(),
        ]);
    }

    public function showMember(Request $request, string $id): JsonResponse
    {
        $club = $request->user();
        $membership = ClubMembership::where('id', $id)
            ->where('club_id', $club->id)
            ->first();

        if (! $membership) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.',
                'error_code' => ApiErrorCode::RECORD_NOT_FOUND,
                'errors' => new \stdClass(),
            ], 404);
        }

        $player = $membership->player;
        $nameParts = explode(' ', trim($player->name ?? ''), 2);
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[1] ?? '';

        $statusHistory = [];

        $req = ClubMembershipRequest::where('club_id', $club->id)
            ->where('player_id', $membership->player_id)
            ->first();
        if ($req) {
            $statusHistory[] = [
                'status' => $req->status,
                'membership_number' => $req->membership_number,
                'date' => $req->created_at?->toIso8601String(),
                'type' => 'request',
            ];
            if ($req->reviewed_at) {
                $statusHistory[] = [
                    'status' => $req->status,
                    'date' => $req->reviewed_at?->toIso8601String(),
                    'type' => 'review',
                ];
            }
        }

        $logs = \App\Models\AuditLog::where('entity_type', ClubMembership::class)
            ->where('entity_id', $membership->id)
            ->oldest()
            ->get();
        foreach ($logs as $log) {
            $statusHistory[] = [
                'status' => $log->action === 'add_member' ? 'approved' : ($log->action === 'remove_member' ? 'removed' : $log->action),
                'date' => $log->created_at?->toIso8601String(),
                'type' => 'audit',
            ];
        }

        usort($statusHistory, fn($a, $b) => strcmp($a['date'], $b['date']));

        return response()->json([
            'success' => true,
            'message' => 'Member details retrieved successfully.',
            'data' => [
                'membership_id' => $membership->id,
                'membership_number' => $membership->membership_number,
                'verification_mode' => $membership->verification_mode,
                'status' => $membership->status,
                'approved_at' => $membership->approved_at?->toIso8601String(),
                'membership_type' => $membership->membership_type ?? 'temporary',
                'membership_expiry_date' => $membership->membership_expiry_date?->toIso8601String(),
                'removed_at' => $membership->removed_at?->toIso8601String(),
                'booking_eligible' => ($membership->status === 'approved' && $player->status === 'active'),
                'status_history' => $statusHistory,
                'player' => [
                    'id' => $player->id,
                    'full_name' => $player->name,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $player->email,
                    'phone' => $player->phone,
                    'profile_image_url' => $player->profile_image ? (str_starts_with($player->profile_image, 'http') ? $player->profile_image : \Illuminate\Support\Facades\Storage::disk('public')->url($player->profile_image)) : null,
                    'dob' => $player->dob?->format('Y-m-d'),
                    'gender' => $player->gender,
                    'playing_level' => $player->playing_level,
                    'primary_hand' => $player->primary_hand,
                    'bio' => $player->bio,
                ],
            ]
        ]);
    }
}

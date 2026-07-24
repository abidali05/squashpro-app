<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Tournament;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PlayerDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $today = Carbon::today('Asia/Karachi')->toDateString();
        $now = Carbon::now('Asia/Karachi')->format('H:i:s');
        $user = $request->user();

        $memberships = \App\Models\ClubMembership::where('player_id', $user->id)->get()->groupBy('club_id');
        $requests = \App\Models\ClubMembershipRequest::where('player_id', $user->id)->get()->groupBy('club_id');

        $clubs = User::query()
            ->where('role', 'club')
            ->where('status', 'active')
            ->withMin(['courts as lowest_court_price' => fn ($query) => $query->where('status', 'active')], 'price_per_hour')
            ->withAvg('bookingReviewsAsClub as rating', 'rating')
            ->orderBy('id')
            ->limit(4)
            ->get()
            ->map(function (User $club) use ($user, $memberships, $requests) {
                $clubId = $club->id;
                $approved = isset($memberships[$clubId]) ? $memberships[$clubId]->where('status', 'approved')->first() : null;
                $membershipStatus = null;
                $membershipNumber = null;
                $isMember = false;

                if ($approved) {
                    $isMember = true;
                    $membershipStatus = 'approved';
                    $membershipNumber = $approved->membership_number;
                } else {
                    $pending = isset($requests[$clubId]) ? $requests[$clubId]->where('status', 'pending')->first() : null;
                    if ($pending) {
                        $membershipStatus = 'pending';
                        $membershipNumber = $pending->membership_number;
                    } else {
                        $rejected = isset($requests[$clubId]) ? $requests[$clubId]->where('status', 'rejected')->first() : null;
                        if ($rejected) {
                            $membershipStatus = 'rejected';
                            $membershipNumber = $rejected->membership_number;
                        } else {
                            $removed = isset($memberships[$clubId]) ? $memberships[$clubId]->where('status', 'removed')->first() : null;
                            if ($removed) {
                                $membershipStatus = 'removed';
                                $membershipNumber = $removed->membership_number;
                            }
                        }
                    }
                }

                $allowNonMemberBooking = (bool) $club->non_member_booking_allowed;
                $canBook = $isMember || $allowNonMemberBooking;
                $requiresPayment = !$isMember;

                return [
                    'id' => $club->id,
                    'name' => $club->club_name ?? $club->name,
                    'image' => $this->imageUrl($club->club_logo),
                    'rating' => round((float) ($club->rating ?? 0), 1),
                    'lowest_court_price' => $this->normalizeNumber($club->lowest_court_price ?? 0),

                    // MaxSquash v1.4 fields
                    'allow_non_member_booking' => $allowNonMemberBooking,
                    'non_member_booking_start_time' => $allowNonMemberBooking ? ($club->non_member_booking_start_time ? substr($club->non_member_booking_start_time, 0, 5) : null) : null,
                    'non_member_booking_end_time' => $allowNonMemberBooking ? ($club->non_member_booking_end_time ? substr($club->non_member_booking_end_time, 0, 5) : null) : null,
                    'is_member' => $isMember,
                    'membership_status' => $membershipStatus,
                    'membership_number' => $membershipNumber,
                    'can_book' => $canBook,
                    'requires_payment' => $requiresPayment,
                ];
            })
            ->values();

        $upcomingBooking = Booking::query()
            ->with(['club', 'court'])
            ->when($user?->role === 'player', function ($query) use ($user, $today, $now) {
                $query->where('player_id', $user->id)
                    ->whereIn('booking_status', ['pending', 'confirmed'])
                    ->where(function ($query) use ($today, $now) {
                        $query->whereDate('booking_date', '>', $today)
                            ->orWhere(function ($query) use ($today, $now) {
                                $query->whereDate('booking_date', $today)
                                    ->where('start_time', '>', $now);
                            });
                    });
            })
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->first();

        $allTournaments = Tournament::query()
            ->with('club')
            ->whereDate('registration_deadline', '>=', $today)
            ->whereDate('start_date', '>=', $today)
            ->orderBy('start_date')
            ->get();

        $activeTournaments = $allTournaments->filter(function (Tournament $tournament) use ($user) {
            // 1. Membership Check: Player must be an approved member of the organizing club (or the opponent club for CLUB_TO_CLUB tournaments)
            $isMember = \App\Models\ClubMembership::where('player_id', $user->id)
                ->where('status', 'approved')
                ->where(function ($query) use ($tournament) {
                    $query->where('club_id', $tournament->club_id)
                        ->when($tournament->opponent_club_id, fn ($q) => $q->orWhere('club_id', $tournament->opponent_club_id));
                })
                ->exists();

            if (!$isMember) {
                return false;
            }

            // 2. Criteria Matching (gender, player_level, age_group):
            
            // Gender match
            if ($tournament->gender && $tournament->gender !== 'OPEN' && $tournament->gender !== 'MIXED') {
                if (strcasecmp((string) $user->gender, (string) $tournament->gender) !== 0) {
                    return false;
                }
            }

            // Level match
            if ($tournament->player_level && is_array($tournament->player_level)) {
                $playerLevel = strtoupper((string) $user->playing_level);
                $allowedLevels = array_map('strtoupper', $tournament->player_level);
                if (!in_array($playerLevel, $allowedLevels, true)) {
                    return false;
                }
            }

            // Age match
            if ($tournament->age_group && $user->dob) {
                $ageRange = explode('-', (string) $tournament->age_group);
                $minAge = isset($ageRange[0]) ? (int) $ageRange[0] : null;
                $maxAge = isset($ageRange[1]) ? (int) $ageRange[1] : null;
                if ($minAge !== null && $maxAge !== null) {
                    $age = $user->dob->age;
                    if ($age < $minAge || $age > $maxAge) {
                        return false;
                    }
                }
            }

            return true;
        })
        ->slice(0, 2)
        ->map(fn (Tournament $tournament) => [
            'id' => $tournament->id,
            'title' => $tournament->name,
            'image' => $this->imageUrl($tournament->tournament_image),
            'start_date' => $tournament->start_date?->toDateString(),
            'registration_status' => $tournament->status,
        ])
        ->values();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard data fetched successfully.',
            'data' => [
                'clubs' => $clubs,
                'upcoming_booking' => $upcomingBooking ? [
                    'id' => $upcomingBooking->id,
                    'club_name' => $upcomingBooking->club?->club_name ?? $upcomingBooking->club?->name,
                    'court_name' => $upcomingBooking->court?->name,
                    'booking_date' => $upcomingBooking->booking_date?->toDateString(),
                    'start_time' => substr((string) $upcomingBooking->start_time, 0, 5),
                    'end_time' => substr((string) $upcomingBooking->end_time, 0, 5),
                    'status' => $upcomingBooking->booking_status,
                ] : null,
                'active_tournaments' => $activeTournaments,
            ],
        ]);
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

    private function normalizeNumber(mixed $value): int|float
    {
        $numeric = (float) ($value ?? 0);

        return $numeric == (int) $numeric ? (int) $numeric : $numeric;
    }
}

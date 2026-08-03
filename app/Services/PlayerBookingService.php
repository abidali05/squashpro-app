<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtTimeSlot;
use App\Models\User;
use App\Notifications\Booking\BookingCancelledByPlayerNotification;
use App\Notifications\Booking\BookingCreatedNotification;
use App\Support\ApiErrorCode;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PlayerBookingService
{
    public function clubs(User $player, bool $lowestPrice = false, bool $openNow = false, int $page = 1, int $limit = 10): array
    {
        $memberships = \App\Models\ClubMembership::where('player_id', $player->id)->get()->groupBy('club_id');
        $requests = \App\Models\ClubMembershipRequest::where('player_id', $player->id)->get()->groupBy('club_id');

        $clubs = User::query()
            ->where('role', 'club')
            ->where('status', 'active')
            ->get()
            ->map(fn (User $club) => $this->clubSummary($club, $player, $memberships, $requests));

        if ($openNow) {
            $clubs = $clubs->filter(fn (array $club) => $club['is_open_now'])->values();
        }

        if ($lowestPrice) {
            $clubs = $clubs->sortBy([
                ['lowest_court_price', 'asc'],
                ['id', 'asc'],
            ])->values();
        } else {
            $clubs = $clubs->sortBy('id')->values();
        }

        $totalRecords = $clubs->count();
        $items = $clubs->forPage($page, $limit)->values();
        $totalPages = (int) max(1, (int) ceil($totalRecords / $limit));

        return [
            'items' => $items,
            'pagination' => [
                'current_page' => $page,
                'limit' => $limit,
                'total_records' => $totalRecords,
                'total_pages' => $totalPages,
            ],
        ];
    }

    public function clubDetails(User $player, int $clubId): array
    {
        $club = $this->findClub($clubId);
        $memberships = \App\Models\ClubMembership::where('player_id', $player->id)->get()->groupBy('club_id');
        $requests = \App\Models\ClubMembershipRequest::where('player_id', $player->id)->get()->groupBy('club_id');

        return $this->clubDetail($club, $player, $memberships, $requests);
    }

    public function clubCourts(int $clubId, string $date): array
    {
        $club = $this->findClub($clubId);
        $courts = $club->courts()->orderBy('id')->get();

        $day = strtolower(\Carbon\Carbon::parse($date)->format('l'));
        $win = \App\Models\ClubNonMemberWindow::where('club_id', $club->id)
            ->where('day', $day)
            ->first();

        $fromTime = $club->non_member_booking_allowed ? ($win ? ($win->from_time ? substr((string)$win->from_time, 0, 5) : null) : ($club->non_member_booking_start_time ? substr((string)$club->non_member_booking_start_time, 0, 5) : null)) : null;
        $toTime = $club->non_member_booking_allowed ? ($win ? ($win->to_time ? substr((string)$win->to_time, 0, 5) : null) : ($club->non_member_booking_end_time ? substr((string)$club->non_member_booking_end_time, 0, 5) : null)) : null;

        return $courts->map(function (Court $court) use ($club, $fromTime, $toTime) {
            $status = match (true) {
                in_array($court->status, ['maintenance', 'inactive'], true) => 'maintenance',
                $court->status === 'active' => 'available',
                default => 'unavailable',
            };

            return [
                'court_id' => $court->id,
                'club_id' => $club->id,
                'court_name' => $court->name,
                'court_type' => Str::headline((string) $court->type),
                'price_per_slot' => $this->normalizeNumber($court->price_per_hour),
                'status' => $status,
                'status_label' => match ($status) {
                    'maintenance' => 'Under Maintenance',
                    'available' => 'Available',
                    default => 'Unavailable',
                },
                'allow_non_member_booking' => (bool) $club->non_member_booking_allowed,
                'non_member_booking_start_time' => $fromTime,
                'non_member_booking_end_time' => $toTime,
            ];
        })->values()->all();
    }

    public function timeSlots(int $clubId, int $courtId, string $date): array
    {
        $club = $this->findClub($clubId);
        $court = $this->findCourt($courtId);
        $today = Carbon::today('Asia/Karachi')->toDateString();
        $now = Carbon::now('Asia/Karachi');

        if ((int) $court->club_id !== $club->id) {
            $this->apiError('Selected court does not belong to selected club.', ApiErrorCode::COURT_NOT_IN_CLUB);
        }

        $slots = $this->ensureSlotsForCourt($club, $court, $date);
        $blockedCourt = in_array($court->status, ['maintenance', 'inactive'], true);

        return [
            'club_id' => $club->id,
            'court_id' => $court->id,
            'date' => $date,
            'slots' => $slots->map(function (CourtTimeSlot $slot) use ($blockedCourt, $today, $now, $court) {
                $status = $this->slotDisplayStatus($slot, $court, $today, $now, $blockedCourt);

                return [
                    'slot_id' => $slot->id,
                    'start_time' => substr((string) $slot->start_time, 0, 5),
                    'end_time' => substr((string) $slot->end_time, 0, 5),
                    'status' => $status,
                    'price' => $this->normalizeNumber($slot->price),
                ];
            })->values()->all(),
        ];
    }

    public function storeBooking(User $player, array $data): Booking
    {
        return DB::transaction(function () use ($player, $data) {
            $club = $this->findClub((int) $data['club_id']);
            $court = $this->findCourt((int) $data['court_id']);

            if ((int) $court->club_id !== $club->id) {
                $this->apiError('Selected court does not belong to selected club.', ApiErrorCode::COURT_NOT_IN_CLUB);
            }

            if (in_array($court->status, ['maintenance', 'inactive'], true)) {
                $this->apiError('Court is under maintenance.', ApiErrorCode::COURT_UNDER_MAINTENANCE);
            }
            $slot = CourtTimeSlot::query()
                ->whereKey((int) $data['slot_id'])
                ->where('court_id', $court->id)
                ->where('club_id', $club->id)
                ->whereDate('booking_date', $data['booking_date'])
                ->first();

            if (! $slot) {
                $this->apiError('Selected time slot is not found.', ApiErrorCode::RECORD_NOT_FOUND, 404);
            }

            if ($slot->status === 'booked' || $slot->booking_id) {
                $this->apiError('Selected time slot is already booked.', ApiErrorCode::SLOT_ALREADY_BOOKED);
            }

            if ($this->slotDisplayStatus($slot, $court, Carbon::today('Asia/Karachi')->toDateString(), Carbon::now('Asia/Karachi')) !== 'available') {
                $this->apiError('Selected time slot is unavailable.', ApiErrorCode::COURT_UNAVAILABLE);
            }

            $workingHours = $this->parseWorkingHours($club->working_hours);
            if (! $this->slotWithinClubHours($slot, $workingHours)) {
                $this->apiError('Club is closed at selected time.', ApiErrorCode::CLUB_CLOSED);
            }

            // Check if player has active approved membership
            $membership = \App\Models\ClubMembership::where('player_id', $player->id)
                ->where('club_id', $club->id)
                ->where('status', 'approved')
                ->first();

            $isApprovedMember = (bool) $membership;

            if (!$isApprovedMember) {
                // Check if non-member booking is allowed
                if (!$club->non_member_booking_allowed) {
                    $this->apiError('Non-member booking is disabled for this club. Only approved club members are allowed to book.', ApiErrorCode::NON_MEMBER_BOOKING_DISABLED, 403);
                }

                // Check non-member allowed daily window
                if ($club->non_member_booking_start_time && $club->non_member_booking_end_time) {
                    $slotStart = substr($slot->start_time, 0, 5);
                    $slotEnd = substr($slot->end_time, 0, 5);
                    $allowedStart = substr($club->non_member_booking_start_time, 0, 5);
                    $allowedEnd = substr($club->non_member_booking_end_time, 0, 5);

                    $startMatch = false;
                    $endMatch = false;

                    if ($allowedStart <= $allowedEnd) {
                        $startMatch = ($slotStart >= $allowedStart && $slotStart <= $allowedEnd);
                        $endMatch = ($slotEnd >= $allowedStart && $slotEnd <= $allowedEnd);
                    } else {
                        $startMatch = ($slotStart >= $allowedStart || $slotStart <= $allowedEnd);
                        $endMatch = ($slotEnd >= $allowedStart || $slotEnd <= $allowedEnd);
                    }

                    if (!$startMatch || !$endMatch) {
                        $this->apiError("Non-members are only allowed to book within the time window: {$allowedStart} - {$allowedEnd}.", ApiErrorCode::OUTSIDE_NON_MEMBER_WINDOW, 422);
                    }
                }
            }

            $booking = Booking::create([
                'club_id' => $club->id,
                'court_id' => $court->id,
                'player_id' => $player->id,
                'slot_id' => $slot->id,
                'booking_date' => $data['booking_date'],
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'booking_status' => $isApprovedMember ? 'confirmed' : 'pending',
                'payment_status' => 'paid',
                'payment_method' => $isApprovedMember ? 'free_membership_allowance' : ($data['payment_method'] ?? 'cash'),
                'payment_transaction_id' => $isApprovedMember ? 'FREE_MEMBER_BOOKING' : ($data['payment_transaction_id'] ?? null),
                'court_price' => $slot->price,
                'service_fee' => 0,
                'total_amount' => $slot->price,
                'currency' => 'PKR',
            ]);

            $slot->update([
                'status' => 'booked',
                'booking_id' => $booking->id,
            ]);

            $booking = $booking->load(['club', 'court', 'player', 'slot']);
            $booking->club->notify((new BookingCreatedNotification($booking))->afterCommit());

            return $booking;
        });
    }

    public function playerBookings(User $player, ?string $filter = null, int $page = 1, int $limit = 10): array
    {
        $today = Carbon::today('Asia/Karachi')->toDateString();

        $query = Booking::query()
            ->with(['club', 'court'])
            ->where('player_id', $player->id);

        match ($filter) {
            'upcoming' => $query
                ->whereNotIn('booking_status', ['cancelled', 'completed', 'failed'])
                ->whereDate('booking_date', '>=', $today),
            // 'completed' => $query
            //     ->where(function ($query) use ($today) {
            //         $query->where('booking_status', 'completed')
            //             ->orWhere(function ($query) use ($today) {
            //                 $query->whereDate('booking_date', '<', $today)
            //                     ->whereNotIn('booking_status', ['cancelled', 'failed']);
            //             });
            //     }),
            'completed' => $query->where('booking_status', 'completed'),
            'cancelled' => $query->where('booking_status', 'cancelled'),
            default => null,
        };

        $bookings = $query
            ->orderByDesc('booking_date')
            ->orderByDesc('start_time')
            ->paginate($limit, ['*'], 'page', $page);

        return [
            'items' => $bookings->items(),
            'pagination' => $this->pagination($bookings, $limit),
        ];
    }

    public function playerBookingDetail(User $player, int $bookingId): Booking
    {
        $booking = Booking::query()
            ->with(['club', 'court'])
            ->whereKey($bookingId)
            ->where('player_id', $player->id)
            ->first();

        if (! $booking) {
            $this->apiError('Booking does not exist.', ApiErrorCode::RECORD_NOT_FOUND, 404);
        }

        return $booking;
    }

    public function cancelBooking(User $player, int $bookingId): Booking
    {
        return DB::transaction(function () use ($player, $bookingId) {
            $booking = Booking::query()
                ->with(['club', 'court', 'slot'])
                ->whereKey($bookingId)
                ->where('player_id', $player->id)
                ->lockForUpdate()
                ->first();

            if (! $booking) {
                $this->apiError('Booking does not exist.', ApiErrorCode::RECORD_NOT_FOUND, 404);
            }

            if (in_array($booking->booking_status, ['cancelled', 'completed', 'failed'], true)) {
                $this->apiError('This booking cannot be cancelled.', ApiErrorCode::VALIDATION_ERROR);
            }

            if (! $this->isUpcomingBooking($booking)) {
                $this->apiError('Only upcoming bookings can be cancelled.', ApiErrorCode::VALIDATION_ERROR);
            }

            $booking->booking_status = 'cancelled';
            $booking->save();

            if ($booking->slot) {
                $booking->slot->update([
                    'status' => 'available',
                    'booking_id' => null,
                ]);
            }

            $booking = $booking->refresh()->load(['club', 'court', 'player']);
            $booking->club->notify((new BookingCancelledByPlayerNotification($booking))->afterCommit());

            return $booking;
        });
    }

    private function resolveMembership(User $player, int $clubId, $memberships, $requests): array
    {
        $approved = isset($memberships[$clubId]) ? $memberships[$clubId]->where('status', 'approved')->first() : null;
        if ($approved) {
            return [
                'is_member' => true,
                'membership_status' => 'approved',
                'membership_number' => $approved->membership_number,
            ];
        }

        $pending = isset($requests[$clubId]) ? $requests[$clubId]->where('status', 'pending')->first() : null;
        if ($pending) {
            return [
                'is_member' => false,
                'membership_status' => 'pending',
                'membership_number' => $pending->membership_number,
            ];
        }

        $rejected = isset($requests[$clubId]) ? $requests[$clubId]->where('status', 'rejected')->first() : null;
        if ($rejected) {
            return [
                'is_member' => false,
                'membership_status' => 'rejected',
                'membership_number' => $rejected->membership_number,
            ];
        }

        $removed = isset($memberships[$clubId]) ? $memberships[$clubId]->where('status', 'removed')->first() : null;
        if ($removed) {
            return [
                'is_member' => false,
                'membership_status' => 'removed',
                'membership_number' => $removed->membership_number,
            ];
        }

        return [
            'is_member' => false,
            'membership_status' => null,
            'membership_number' => null,
        ];
    }

    private function clubSummary(User $club, User $player, $memberships, $requests): array
    {
        $workingHours = $this->parseWorkingHours($club->working_hours);
        $lowestPrice = $this->lowestCourtPrice($club);

        $membershipInfo = $this->resolveMembership($player, $club->id, $memberships, $requests);

        $allowNonMemberBooking = (bool) $club->non_member_booking_allowed;
        $isMember = $membershipInfo['is_member'];
        $canBook = $isMember || $allowNonMemberBooking;
        $requiresPayment = !$isMember;

        return [
            'id' => $club->id,
            'club_name' => $club->club_name ?? $club->name,
            'image' => $this->imageUrl($club->club_logo),
            'address' => $club->address,
            'city' => $club->city,
            'opening_time' => $workingHours['start'],
            'closing_time' => $workingHours['end'],
            'is_open_now' => $this->isOpenNow($club, $workingHours),
            'lowest_court_price' => $lowestPrice,
            'total_courts' => $club->courts()->count(),

            // Required MaxSquash v1.4 fields
            'allow_non_member_booking' => $allowNonMemberBooking,
            'non_member_booking_start_time' => $allowNonMemberBooking ? ($club->non_member_booking_start_time ? substr($club->non_member_booking_start_time, 0, 5) : null) : null,
            'non_member_booking_end_time' => $allowNonMemberBooking ? ($club->non_member_booking_end_time ? substr($club->non_member_booking_end_time, 0, 5) : null) : null,
            'is_member' => $isMember,
            'membership_status' => $membershipInfo['membership_status'],
            'membership_number' => $membershipInfo['membership_number'],
            'can_book' => $canBook,
            'requires_payment' => $requiresPayment,
        ];
    }

    private function clubDetail(User $club, User $player, $memberships = null, $requests = null): array
    {
        $workingHours = $this->parseWorkingHours($club->working_hours);

        if ($memberships === null) {
            $memberships = \App\Models\ClubMembership::where('player_id', $player->id)->get()->groupBy('club_id');
        }
        if ($requests === null) {
            $requests = \App\Models\ClubMembershipRequest::where('player_id', $player->id)->get()->groupBy('club_id');
        }

        $membershipInfo = $this->resolveMembership($player, $club->id, $memberships, $requests);

        $allowNonMemberBooking = (bool) $club->non_member_booking_allowed;
        $isMember = $membershipInfo['is_member'];
        $canBook = $isMember || $allowNonMemberBooking;
        $requiresPayment = !$isMember;

        $dbWorkingHours = \App\Models\ClubWorkingHour::where('club_id', $club->id)->get();
        if ($dbWorkingHours->isNotEmpty()) {
            $workingHoursList = $dbWorkingHours->map(function ($wh) {
                return [
                    'day' => $wh->day,
                    'is_open' => (bool) $wh->is_open,
                    'opens_at' => $wh->opens_at ? substr((string)$wh->opens_at, 0, 5) : null,
                    'closes_at' => $wh->closes_at ? substr((string)$wh->closes_at, 0, 5) : null,
                ];
            })->all();
        } else {
            $whParsed = $this->parseWorkingHours($club->working_hours);
            $workingHoursList = [];
            $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            foreach ($daysOfWeek as $d) {
                $workingHoursList[] = [
                    'day' => $d,
                    'is_open' => $whParsed['start'] && $whParsed['end'] ? true : false,
                    'opens_at' => $whParsed['start'],
                    'closes_at' => $whParsed['end'],
                ];
            }
        }

        $dbWindows = \App\Models\ClubNonMemberWindow::where('club_id', $club->id)->get();
        if ($dbWindows->isNotEmpty()) {
            $nonMemberBookingSchedule = $dbWindows->map(function ($win) {
                return [
                    'day' => $win->day,
                    'is_available' => (bool) $win->is_available,
                    'from_time' => $win->from_time ? substr((string)$win->from_time, 0, 5) : null,
                    'to_time' => $win->to_time ? substr((string)$win->to_time, 0, 5) : null,
                ];
            })->all();
        } else {
            $nonMemberBookingSchedule = [];
            $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            foreach ($daysOfWeek as $d) {
                $nonMemberBookingSchedule[] = [
                    'day' => $d,
                    'is_available' => $allowNonMemberBooking,
                    'from_time' => $allowNonMemberBooking && $club->non_member_booking_start_time ? substr($club->non_member_booking_start_time, 0, 5) : null,
                    'to_time' => $allowNonMemberBooking && $club->non_member_booking_end_time ? substr($club->non_member_booking_end_time, 0, 5) : null,
                ];
            }
        }

        return [
            'club_id' => $club->id,
            'club_name' => $club->club_name ?? $club->name,
            'images' => array_values(array_filter([$this->imageUrl($club->club_logo)])),
            'address' => $club->address,
            'city' => $club->city,
            'description' => $club->bio,
            'phone' => $club->phone,
            'opening_time' => $workingHours['start'],
            'closing_time' => $workingHours['end'],
            'is_open_now' => $this->isOpenNow($club, $workingHours),
            'facilities' => $club->facilities ?? [],
            'courts_count' => $club->courts()->count(),
            'lowest_court_price' => $this->lowestCourtPrice($club),
            'working_hours' => $workingHoursList,

            // Required MaxSquash v1.4 fields
            'allow_non_member_booking' => $allowNonMemberBooking,
            'non_member_booking_start_time' => $allowNonMemberBooking ? ($club->non_member_booking_start_time ? substr($club->non_member_booking_start_time, 0, 5) : null) : null,
            'non_member_booking_end_time' => $allowNonMemberBooking ? ($club->non_member_booking_end_time ? substr($club->non_member_booking_end_time, 0, 5) : null) : null,
            'non_member_booking_schedule' => $nonMemberBookingSchedule,
            'is_member' => $isMember,
            'membership_status' => $membershipInfo['membership_status'],
            'membership_number' => $membershipInfo['membership_number'],
            'can_book' => $canBook,
            'requires_payment' => $requiresPayment,
        ];
    }

    private function ensureSlotsForCourt(User $club, Court $court, string $date): Collection
    {
        $existing = CourtTimeSlot::query()
            ->where('club_id', $club->id)
            ->where('court_id', $court->id)
            ->whereDate('booking_date', $date)
            ->orderBy('start_time')
            ->get();

        if ($existing->isNotEmpty()) {
            return $existing;
        }

        $workingHours = $this->parseWorkingHours($club->working_hours);
        $startTime = Carbon::createFromFormat('H:i', $workingHours['start'], 'Asia/Karachi');
        $endTime = Carbon::createFromFormat('H:i', $workingHours['end'], 'Asia/Karachi');

        if ($endTime->lessThanOrEqualTo($startTime)) {
            $startTime = Carbon::createFromFormat('H:i', '08:00', 'Asia/Karachi');
            $endTime = Carbon::createFromFormat('H:i', '23:00', 'Asia/Karachi');
        }

        $slots = [];
        $cursor = $startTime->copy();
        while ($cursor->copy()->addHour()->lessThanOrEqualTo($endTime)) {
            $slotStart = $cursor->copy();
            $slotEnd = $cursor->copy()->addHour();
            $slots[] = CourtTimeSlot::create([
                'club_id' => $club->id,
                'court_id' => $court->id,
                'booking_date' => $date,
                'start_time' => $slotStart->format('H:i:s'),
                'end_time' => $slotEnd->format('H:i:s'),
                'status' => in_array($court->status, ['maintenance', 'inactive'], true) ? 'blocked' : 'available',
                'price' => $court->price_per_hour,
            ]);
            $cursor->addHour();
        }

        return collect($slots);
    }

    private function findClub(int $clubId): User
    {
        $club = User::query()
            ->whereKey($clubId)
            ->where('role', 'club')
            ->where('status', 'active')
            ->first();

        if (! $club) {
            $this->apiError('Club does not exist.', ApiErrorCode::CLUB_NOT_FOUND, 404);
        }

        return $club;
    }

    private function findCourt(int $courtId): Court
    {
        $court = Court::query()->whereKey($courtId)->first();

        if (! $court) {
            $this->apiError('Court does not exist.', ApiErrorCode::COURT_NOT_FOUND, 404);
        }

        return $court;
    }

    private function lowestCourtPrice(User $club): int|float
    {
        $price = $club->courts()
            ->where('status', 'active')
            ->min('price_per_hour');

        return $this->normalizeNumber($price ?? 0);
    }

    private function parseWorkingHours(?string $workingHours): array
    {
        if ($workingHours && preg_match('/(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})/', $workingHours, $matches)) {
            return ['start' => $matches[1], 'end' => $matches[2]];
        }

        return ['start' => '08:00', 'end' => '23:00'];
    }

    private function isOpenNow(User $club, array $workingHours): bool
    {
        if ($club->status !== 'active') {
            return false;
        }

        $now = Carbon::now('Asia/Karachi');
        $start = Carbon::createFromFormat('H:i', $workingHours['start'], 'Asia/Karachi')->setDateFrom($now);
        $end = Carbon::createFromFormat('H:i', $workingHours['end'], 'Asia/Karachi')->setDateFrom($now);

        if ($end->lessThan($start)) {
            return $now->greaterThanOrEqualTo($start) || $now->lessThanOrEqualTo($end);
        }

        return $now->betweenIncluded($start, $end);
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

    private function pagination(LengthAwarePaginator $paginator, int $limit): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'limit' => $limit,
            'total_records' => $paginator->total(),
            'total_pages' => $paginator->lastPage(),
        ];
    }

    private function isUpcomingBooking(Booking $booking): bool
    {
        $bookingDate = $booking->booking_date?->toDateString();
        $today = Carbon::today('Asia/Karachi')->toDateString();

        if ($bookingDate > $today) {
            return true;
        }

        if ($bookingDate < $today) {
            return false;
        }

        $startTime = Carbon::createFromFormat('H:i:s', (string) $booking->start_time, 'Asia/Karachi')
            ->setDateFrom(Carbon::now('Asia/Karachi'));

        return $startTime->greaterThan(Carbon::now('Asia/Karachi'));
    }

    private function slotWithinClubHours(CourtTimeSlot $slot, array $workingHours): bool
    {
        $slotStart = Carbon::createFromFormat('H:i:s', $slot->start_time, 'Asia/Karachi');
        $slotEnd = Carbon::createFromFormat('H:i:s', $slot->end_time, 'Asia/Karachi');
        $open = Carbon::createFromFormat('H:i', $workingHours['start'], 'Asia/Karachi')->setDateFrom($slotStart);
        $close = Carbon::createFromFormat('H:i', $workingHours['end'], 'Asia/Karachi')->setDateFrom($slotStart);

        if ($close->lessThan($open)) {
            return true;
        }

        return $slotStart->greaterThanOrEqualTo($open) && $slotEnd->lessThanOrEqualTo($close);
    }

    private function slotDisplayStatus(CourtTimeSlot $slot, Court $court, string $today, Carbon $now, bool $forceBlockedCourt = false): string
    {
        if ($forceBlockedCourt || in_array($court->status, ['maintenance', 'inactive'], true)) {
            return 'blocked';
        }

        $slotDate = Carbon::parse($slot->booking_date)->toDateString();
        $slotStart = Carbon::createFromFormat('H:i:s', $slot->start_time, 'Asia/Karachi')->setDateFrom($now);
        if ($slotDate === $today && $slotStart->lessThanOrEqualTo($now)) {
            return 'blocked';
        }

        if ($slot->status === 'booked' || $slot->booking_id) {
            return 'booked';
        }

        if ($slot->status === 'blocked') {
            return 'blocked';
        }

        return 'available';
    }

    private function apiError(string $message, string $code, int $status = 422): never
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => $message,
            'error' => [
                'code' => $code,
            ],
        ], $status));
    }
}

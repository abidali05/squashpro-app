<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ClubMembership;
use App\Models\ClubMembershipRequest;
use App\Models\ClubNonMemberWindow;
use App\Models\ClubTournamentPool;
use App\Models\ClubTournamentRule;
use App\Models\ClubWorkingHour;
use App\Models\Court;
use App\Models\CourtStatusAudit;
use App\Models\Tournament;
use App\Models\TournamentFixture;
use App\Models\TournamentGroup;
use App\Models\TournamentInvitation;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistration;
use App\Models\TournamentTeam;
use App\Models\TournamentTeamPlayer;
use App\Models\User;
use App\Notifications\Booking\BookingStatusUpdatedNotification;
use App\Notifications\Court\CourtCreatedNotification;
use App\Notifications\Court\CourtMaintenanceNotification;
use App\Notifications\Tournament\TournamentAvailableNotification;
use App\Notifications\Tournament\TournamentCreatedNotification;
use App\Notifications\Tournament\TournamentInvitationAcceptedNotification;
use App\Notifications\Tournament\TournamentInvitationNotification;
use App\Notifications\Tournament\TournamentInvitationRejectedNotification;
use App\Notifications\Tournament\TournamentTeamSubmittedNotification;
use App\Support\ApiErrorCode;
use App\Support\AuditLogger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ClubService
{
    public function profile(User $club): User
    {
        return $club->loadCount('courts');
    }

    public function updateClubDetails(User $club, array $data): User
    {
        return DB::transaction(function () use ($club, $data) {
            if (array_key_exists('name', $data)) {
                $club->name = $data['name'];
                $club->club_name = $data['name'];
            }

            if (array_key_exists('address', $data)) {
                $club->address = $data['address'];
            }

            if (array_key_exists('working_hours', $data)) {
                $workingHours = $data['working_hours'];
                if (is_array($workingHours)) {
                    ClubWorkingHour::where('club_id', $club->id)->delete();
                    foreach ($workingHours as $wh) {
                        ClubWorkingHour::create([
                            'club_id' => $club->id,
                            'day' => strtolower($wh['day']),
                            'is_open' => filter_var($wh['is_open'], FILTER_VALIDATE_BOOLEAN),
                            'opens_at' => $wh['opens_at'] ?? null,
                            'closes_at' => $wh['closes_at'] ?? null,
                        ]);
                    }

                    $firstOpenWh = collect($workingHours)->firstWhere('is_open', true);
                    if ($firstOpenWh) {
                        $club->working_hours = ($firstOpenWh['opens_at'] ?? '').'-'.($firstOpenWh['closes_at'] ?? '');
                    } else {
                        $club->working_hours = 'Closed';
                    }
                } else {
                    $club->working_hours = $workingHours;

                    $start = null;
                    $end = null;
                    if (preg_match('/(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})/', $workingHours, $matches)) {
                        $start = $matches[1];
                        $end = $matches[2];
                    }
                    if ($start && $end) {
                        ClubWorkingHour::where('club_id', $club->id)->update([
                            'opens_at' => $start,
                            'closes_at' => $end,
                            'is_open' => true,
                        ]);
                    }
                }

                // Check and move mismatching courts to maintenance automatically
                $this->checkAndFlagCourtsForMaintenance($club);
            }

            if (array_key_exists('facilities', $data)) {
                $club->facilities = $this->normalizeFacilities($data['facilities']);
            }

            if (array_key_exists('allow_non_member_booking', $data)) {
                $club->non_member_booking_allowed = filter_var($data['allow_non_member_booking'], FILTER_VALIDATE_BOOLEAN);
                if (! $club->non_member_booking_allowed) {
                    ClubNonMemberWindow::where('club_id', $club->id)->delete();
                    $club->non_member_booking_start_time = null;
                    $club->non_member_booking_end_time = null;
                }
            }

            if ($club->non_member_booking_allowed && isset($data['non_member_booking_schedule']) && is_array($data['non_member_booking_schedule'])) {
                ClubNonMemberWindow::where('club_id', $club->id)->delete();
                foreach ($data['non_member_booking_schedule'] as $nms) {
                    $day = strtolower($nms['day']);
                    $isAvail = filter_var($nms['is_available'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $timeRanges = $nms['time_ranges'] ?? [];

                    if ($isAvail && is_array($timeRanges)) {
                        foreach ($timeRanges as $range) {
                            ClubNonMemberWindow::create([
                                'club_id' => $club->id,
                                'day' => $day,
                                'is_available' => true,
                                'from_time' => $range['from'],
                                'to_time' => $range['to'],
                            ]);
                        }
                    } else {
                        ClubNonMemberWindow::create([
                            'club_id' => $club->id,
                            'day' => $day,
                            'is_available' => false,
                            'from_time' => null,
                            'to_time' => null,
                        ]);
                    }
                }

                $firstAvailDay = collect($data['non_member_booking_schedule'])->first(fn ($nms) => filter_var($nms['is_available'] ?? false, FILTER_VALIDATE_BOOLEAN));
                if ($firstAvailDay && ! empty($firstAvailDay['time_ranges'])) {
                    $club->non_member_booking_start_time = $firstAvailDay['time_ranges'][0]['from'];
                    $club->non_member_booking_end_time = $firstAvailDay['time_ranges'][0]['to'];
                } else {
                    $club->non_member_booking_start_time = null;
                    $club->non_member_booking_end_time = null;
                }
            }

            $club->number_of_courts = $club->courts()->count();
            $club->save();

            return $club->refresh();
        });
    }

    public function checkAndFlagCourtsForMaintenance(User $club): void
    {
        $courts = $club->courts()->get();

        foreach ($courts as $court) {
            $slots = $court->slots()->get();

            if ($slots->isEmpty()) {
                continue;
            }

            $shouldMoveToMaintenance = false;

            foreach ($slots as $slot) {
                $day = strtolower($slot->day);
                $workingHour = ClubWorkingHour::where('club_id', $club->id)
                    ->where('day', $day)
                    ->first();

                if (! $workingHour || ! $workingHour->is_open) {
                    $shouldMoveToMaintenance = true;
                    break;
                }

                $opensAt = substr((string) $workingHour->opens_at, 0, 5);
                $closesAt = substr((string) $workingHour->closes_at, 0, 5);
                $slotStart = substr((string) $slot->start_time, 0, 5);
                $slotEnd = substr((string) $slot->end_time, 0, 5);

                if (strcmp($slotStart, $opensAt) < 0 || strcmp($slotEnd, $closesAt) > 0) {
                    $shouldMoveToMaintenance = true;
                    break;
                }
            }

            if ($shouldMoveToMaintenance && $court->status !== 'maintenance') {
                $previousStatus = $court->status;

                $court->status = 'maintenance';
                $court->maintenance_note = 'Court moved to maintenance because configured slots are outside the updated club working hours.';
                $court->save();

                CourtStatusAudit::create([
                    'court_id' => $court->id,
                    'previous_status' => $previousStatus,
                    'new_status' => 'maintenance',
                    'reason' => 'Court moved to maintenance because configured slots are outside the updated club working hours.',
                    'changed_at' => now(),
                ]);
            }
        }
    }

    public function updateClubLogo(User $club, UploadedFile $logoFile): User
    {
        return DB::transaction(function () use ($club, $logoFile) {
            $this->deleteStoredClubLogo($club->club_logo);
            $club->club_logo = $this->storeUploadedClubLogo($logoFile);
            $club->save();

            return $club->refresh();
        });
    }

    public function dashboard(User $club): array
    {
        $totalCourts = (int) ($club->number_of_courts ?: $club->courts()->count());
        $availableCourts = (int) $club->courts()->where('status', 'active')->count();
        $maintenanceCourts = (int) $club->courts()->whereIn('status', ['maintenance', 'inactive'])->count();
        $courtBooking = $this->countClubBookings($club->id);
        $todayBooking = $this->countClubBookings($club->id, true);
        $pendingBookings = $this->countClubBookings($club->id, false, 'pending');
        $activeTournaments = $this->countClubTournaments($club->id);

        $pendingMemberships = (int) ClubMembershipRequest::where('club_id', $club->id)
            ->where('status', 'pending')
            ->count();

        $pendingTournamentInvitations = (int) Tournament::whereJsonContains('opponent_club_id', (int) $club->id)
            ->where(function ($q) use ($club) {
                $q->whereExists(function ($sub) use ($club) {
                    $sub->select(DB::raw(1))
                        ->from('tournament_invitations')
                        ->whereColumn('tournament_invitations.tournament_id', 'tournaments.id')
                        ->where('tournament_invitations.invited_club_id', $club->id)
                        ->where('tournament_invitations.status', 'pending');
                })->orWhereNotExists(function ($sub) use ($club) {
                    $sub->select(DB::raw(1))
                        ->from('tournament_invitations')
                        ->whereColumn('tournament_invitations.tournament_id', 'tournaments.id')
                        ->where('tournament_invitations.invited_club_id', $club->id);
                });
            })
            ->count();

        return [
            'court_utilization' => $availableCourts.'/'.$totalCourts,
            'court_booking' => $courtBooking,
            'today_booking' => $todayBooking,
            'pending_bookings' => $pendingBookings,
            'total_courts' => $totalCourts,
            'available_courts' => $availableCourts,
            'maintenance_courts' => $maintenanceCourts,
            'active_tournaments' => $activeTournaments,
            'pending_membership_requests' => $pendingMemberships,
            'pending_tournament_invitations' => $pendingTournamentInvitations,
        ];
    }

    public function courts(User $club, ?string $status = null, ?int $limit = null, int $page = 1): array
    {
        $allCourts = $club->courts()->orderBy('id')->get();

        $query = $club->courts()->orderBy('id');
        if ($status === 'available') {
            $query->where('status', 'active');
        } elseif ($status === 'maintenance') {
            $query->where('status', 'maintenance');
        }

        $courts = $limit
            ? collect($query->paginate($limit, ['*'], 'page', $page)->items())
            : $query->get();

        return [
            'counts' => [
                'total_courts' => $allCourts->count(),
                'available_courts' => $allCourts->where('status', 'active')->count(),
                'maintenance_courts' => $allCourts->whereIn('status', ['maintenance', 'inactive'])->count(),
            ],
            'courts' => $courts,
        ];
    }

    public function courtDetail(User $club, string $courtId): Court
    {
        return $this->findClubCourt($club, $courtId);
    }

    public function storeCourt(User $club, array $data): Court
    {
        return DB::transaction(function () use ($club, $data) {
            $court = $club->courts()->create([
                'name' => $data['name'],
                'type' => $data['type'],
                'price_per_hour' => $data['price_per_hour'],
                'status' => $this->mapInputStatusToStorage($data['status']),
                'maintenance_note' => $data['maintenance_note'] ?? null,
            ]);

            if (isset($data['slots']) && is_array($data['slots'])) {
                foreach ($data['slots'] as $slot) {
                    $court->slots()->create([
                        'day' => strtolower($slot['day']),
                        'start_time' => $slot['start_time'],
                        'end_time' => $slot['end_time'],
                        'price' => $slot['price'],
                        'is_available' => $slot['is_available'] ?? true,
                    ]);
                }
            }

            $court = $court->refresh()->load(['club', 'slots']);
            $this->notifyCityPlayersAboutCourtCreated($club, $court);

            return $court;
        });
    }

    public function updateCourt(User $club, string $courtId, array $data): Court
    {
        return DB::transaction(function () use ($club, $courtId, $data) {
            $court = $this->findClubCourt($club, $courtId);

            if (array_key_exists('name', $data)) {
                $court->name = $data['name'];
            }

            if (array_key_exists('type', $data)) {
                $court->type = $data['type'];
            }

            if (array_key_exists('price_per_hour', $data)) {
                $court->price_per_hour = $data['price_per_hour'];
            }

            if (array_key_exists('status', $data)) {
                $court->status = $this->mapInputStatusToStorage($data['status']);
            }

            if (array_key_exists('maintenance_note', $data)) {
                $court->maintenance_note = $data['maintenance_note'];
            }

            if (array_key_exists('slots', $data) && is_array($data['slots'])) {
                $court->slots()->delete();
                foreach ($data['slots'] as $slot) {
                    $court->slots()->create([
                        'day' => strtolower($slot['day']),
                        'start_time' => $slot['start_time'],
                        'end_time' => $slot['end_time'],
                        'price' => $slot['price'],
                        'is_available' => $slot['is_available'] ?? true,
                    ]);
                }
            }

            $court->save();

            return $court->refresh()->load('slots');
        });
    }

    public function setCourtMaintenance(User $club, string $courtId, ?string $reason = null): Court
    {
        return DB::transaction(function () use ($club, $courtId, $reason) {
            $court = $this->findClubCourt($club, $courtId);
            $court->status = 'maintenance';
            $court->maintenance_note = $reason;
            $court->save();

            $court = $court->refresh()->load('club');
            $this->notifyCityPlayersAboutCourtMaintenance($club, $court);

            return $court;
        });
    }

    public function tournaments(User $club, ?string $status = null, ?int $limit = null, int $page = 1): array
    {
        $baseQuery = Tournament::query()
            ->where(function ($q) use ($club) {
                $q->where('club_id', $club->id)
                    ->orWhereJsonContains('opponent_club_id', (int) $club->id);
            });

        $allTournaments = $baseQuery->get();

        $query = Tournament::query()
            ->where(function ($q) use ($club) {
                $q->where('club_id', $club->id)
                    ->orWhereJsonContains('opponent_club_id', (int) $club->id);
            })
            ->orderBy('id', 'desc');

        if ($status && in_array($status, ['pending', 'soft_accepted', 'confirmed', 'rejected', 'open', 'full', 'closed', 'completed', 'cancelled', 'accepted'], true)) {
            $query->where(function ($q) use ($club, $status) {
                // Organizer matches overall status
                $q->where(function ($sub) use ($club, $status) {
                    $sub->where('club_id', $club->id)
                        ->where('status', $status === 'accepted' ? 'soft_accepted' : $status);
                });

                // Invited matches invitation status
                $q->orWhere(function ($sub) use ($club, $status) {
                    $sub->whereJsonContains('opponent_club_id', (int) $club->id);

                    if ($status === 'pending') {
                        $sub->where(function ($pendingSub) use ($club) {
                            $pendingSub->whereExists(function ($subExists) use ($club) {
                                $subExists->select(DB::raw(1))
                                    ->from('tournament_invitations')
                                    ->whereColumn('tournament_invitations.tournament_id', 'tournaments.id')
                                    ->where('tournament_invitations.invited_club_id', $club->id)
                                    ->where('tournament_invitations.status', 'pending');
                            })->orWhereNotExists(function ($subExists) use ($club) {
                                $subExists->select(DB::raw(1))
                                    ->from('tournament_invitations')
                                    ->whereColumn('tournament_invitations.tournament_id', 'tournaments.id')
                                    ->where('tournament_invitations.invited_club_id', $club->id);
                            });
                        });
                    } elseif ($status === 'rejected') {
                        $sub->whereExists(function ($subExists) use ($club) {
                            $subExists->select(DB::raw(1))
                                ->from('tournament_invitations')
                                ->whereColumn('tournament_invitations.tournament_id', 'tournaments.id')
                                ->where('tournament_invitations.invited_club_id', $club->id)
                                ->where('tournament_invitations.status', 'rejected');
                        });
                    } elseif ($status === 'soft_accepted' || $status === 'accepted') {
                        $sub->whereExists(function ($subExists) use ($club) {
                            $subExists->select(DB::raw(1))
                                ->from('tournament_invitations')
                                ->whereColumn('tournament_invitations.tournament_id', 'tournaments.id')
                                ->where('tournament_invitations.invited_club_id', $club->id)
                                ->where('tournament_invitations.status', 'accepted');
                        });
                    } elseif ($status === 'confirmed') {
                        $sub->whereExists(function ($subExists) use ($club) {
                            $subExists->select(DB::raw(1))
                                ->from('tournament_teams')
                                ->whereColumn('tournament_teams.tournament_id', 'tournaments.id')
                                ->where('tournament_teams.club_id', $club->id)
                                ->where('tournament_teams.submission_status', 'submitted');
                        });
                    } else {
                        // For other statuses (like open, full, completed, etc.), match the overall status
                        $sub->where('status', $status);
                    }
                });
            });
        }

        $tournaments = $limit
            ? collect($query->paginate($limit, ['*'], 'page', $page)->items())
            : $query->get();

        return [
            'counts' => [
                'open_tournament_count' => $allTournaments->where('status', 'open')->count(),
                'full_tournament_count' => $allTournaments->where('status', 'full')->count(),
                'total_registered_players' => (int) $allTournaments->sum('registered_players_count'),
            ],
            'tournaments' => $tournaments,
        ];
    }

    public function bookings(User $club, ?string $status = null, ?string $date = null, ?int $limit = null, int $page = 1): array
    {
        $now = Carbon::now('Asia/Karachi');
        $today = $now->toDateString();

        $allBookings = $club->bookingsAsClub()
            ->with(['player:id,name', 'court:id,name'])
            ->orderByDesc('id')
            ->get();

        // Pending count: only bookings whose date+time has NOT passed yet
        $pendingCount = $allBookings
            ->where('booking_status', 'pending')
            ->filter(function (Booking $booking) use ($today, $now): bool {
                $bookingDate = $booking->booking_date?->toDateString();

                if ($bookingDate > $today) {
                    return true;
                }

                if ($bookingDate < $today) {
                    return false;
                }

                // Same day — check start_time hasn't passed
                $startTime = Carbon::createFromFormat('H:i:s', (string) $booking->start_time, 'Asia/Karachi')
                    ->setDateFrom($now);

                return $startTime->greaterThan($now);
            })
            ->count();

        $query = $club->bookingsAsClub()
            ->with(['player:id,name', 'court:id,name'])
            ->orderByDesc('id')
            ->where(function ($q) use ($today, $now): void {
                // Non-pending bookings always show regardless of date
                $q->where('booking_status', '!=', 'pending')
                    // Pending: only future date
                    ->orWhere(function ($q) use ($today): void {
                        $q->where('booking_status', 'pending')
                            ->whereDate('booking_date', '>', $today);
                    })
                    // Pending: same day but start_time hasn't passed yet
                    ->orWhere(function ($q) use ($today, $now): void {
                        $q->where('booking_status', 'pending')
                            ->whereDate('booking_date', $today)
                            ->whereTime('start_time', '>', $now->format('H:i:s'));
                    });
            });

        if ($status && in_array($status, ['pending', 'confirmed', 'cancelled'], true)) {
            $query->where('booking_status', $status);
        }

        if ($date) {
            $query->whereDate('booking_date', $date);
        }

        $bookings = $limit
            ? collect($query->paginate($limit, ['*'], 'page', $page)->items())
            : $query->get();

        return [
            'counts' => [
                'pending_bookings' => $pendingCount,
                'confirmed_bookings' => $allBookings->where('booking_status', 'confirmed')->count(),
                'cancelled_bookings' => $allBookings->where('booking_status', 'cancelled')->count(),
            ],
            'bookings' => $bookings,
        ];
    }

    public function bookingDetail(User $club, string $bookingId): Booking
    {
        return $this->findClubBooking($club, $bookingId);
    }

    public function updateBookingStatus(User $club, string $bookingId, string $status, ?string $rejectionReason = null): Booking
    {
        return DB::transaction(function () use ($club, $bookingId, $status, $rejectionReason) {
            $booking = $this->findClubBooking($club, $bookingId);

            if ($status === 'confirmed' && ! $this->isUpcomingBooking($booking)) {
                $this->apiError('Past bookings cannot be confirmed.', ApiErrorCode::VALIDATION_ERROR);
            }

            $booking->booking_status = $status;
            $booking->rejection_reason = $status === 'cancelled' ? $rejectionReason : null;
            $booking->save();

            $booking = $booking->refresh()->load(['player', 'court', 'club']);

            if (in_array($status, ['confirmed', 'cancelled'], true)) {
                $booking->player->notify((new BookingStatusUpdatedNotification($booking))->afterCommit());
            }

            return $booking;
        });
    }

    public function tournamentDetail(User $club, string $tournamentId): Tournament
    {
        return $this->findClubTournament($club, $tournamentId);
    }

    public function getTournamentRules(User $club, string $tournamentId): ClubTournamentRule
    {
        $tournament = $this->findClubTournament($club, $tournamentId);

        $rules = ClubTournamentRule::where('tournament_id', $tournament->id)->first();
        if (! $rules) {
            $this->apiError('Tournament rules not found.', 'NOT_FOUND', 404);
        }

        return $rules;
    }

    public function storeOrUpdateTournamentRules(User $club, string $tournamentId, array $data): ClubTournamentRule
    {
        $tournament = $this->findClubTournament($club, $tournamentId);

        return ClubTournamentRule::updateOrCreate(
            ['tournament_id' => $tournament->id],
            [
                'club_id' => $club->id,
                'tournament_format' => $data['tournament_format'] ?? $tournament->format,
                'competition_setup' => $data['competition_setup'] ?? null,
                'pool_rules' => $data['pool_rules'] ?? null,
                'knockout_rounds' => $data['knockout_rounds'] ?? null,
                'match_equipment' => $data['match_equipment'] ?? null,
                'scoring_rules' => $data['scoring_rules'] ?? null,
                'note' => $data['note'] ?? null,
            ]
        );
    }

    public function getTournamentPools(User $club, string $tournamentId): ClubTournamentPool
    {
        $tournament = $this->findClubTournament($club, $tournamentId);

        $pools = ClubTournamentPool::where('tournament_id', $tournament->id)->first();
        if (! $pools) {
            $this->apiError('Tournament pools not found.', 'NOT_FOUND', 404);
        }

        return $pools;
    }

    public function storeOrUpdateTournamentPools(User $club, string $tournamentId, array $data): ClubTournamentPool
    {
        $tournament = $this->findClubTournament($club, $tournamentId);

        if (! empty($data['format'])) {
            $tournament->update(['format' => $data['format']]);
        }

        return ClubTournamentPool::updateOrCreate(
            ['tournament_id' => $tournament->id],
            [
                'club_id' => $club->id,
                'format' => $data['format'],
                'has_pools' => (bool) $data['has_pools'],
                'pools' => $data['pools'],
            ]
        );
    }

    public function storeTournament(User $club, array $data, ?UploadedFile $imageFile = null): Tournament
    {
        return DB::transaction(function () use ($club, $data, $imageFile) {
            $tournamentType = $data['tournament_type'] ?? 'CLUB_MEMBERS_ONLY';
            $status = $tournamentType === 'CLUB_TO_CLUB' ? 'pending' : 'open';
            $maximumPlayers = (int) ($data['maximum_players'] ?? $data['allowed_player'] ?? 0);

            $invitedClubIds = array_values(array_map('intval', $data['invited_club_ids'] ?? []));
            if (empty($invitedClubIds) && isset($data['opponent_club_id']) && $data['opponent_club_id'] !== null) {
                $invitedClubIds[] = (int) $data['opponent_club_id'];
            }
            $opponentClubIdValue = ! empty($invitedClubIds) ? $invitedClubIds : null;

            $tournament = $club->tournaments()->create([
                'name' => $data['name'],
                'format' => $data['format'],
                'tournament_image' => $this->storeTournamentImage($imageFile, $data['tournament_image'] ?? null),
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'registration_deadline' => $data['registration_deadline'],
                'entry_fees' => $data['entry_fees'],
                'prize_pool' => $data['prize_pool'],
                'allowed_player' => $maximumPlayers,
                'maximum_players' => $maximumPlayers,
                'registered_players_count' => 0,
                'status' => $status,
                'rules' => $data['rules'] ?? null,

                // New fields
                'tournament_type' => $tournamentType,
                'opponent_club_id' => $opponentClubIdValue,
                'gender' => $data['gender'] ?? 'OPEN',
                'player_level' => $data['player_level'] ?? null,
                'age_group' => $data['age_group'] ?? null,
            ]);

            $tournament = $tournament->refresh()->load('club');

            if ($tournamentType === 'CLUB_TO_CLUB') {
                if (isset($data['scorer_ids']) && is_array($data['scorer_ids'])) {
                    $tournament->scorers()->sync($data['scorer_ids']);
                }
                if (isset($data['umpire_ids']) && is_array($data['umpire_ids'])) {
                    $tournament->umpires()->sync($data['umpire_ids']);
                }
                // 1. Create separate invitation records for every invited club
                foreach ($invitedClubIds as $invitedId) {
                    TournamentInvitation::create([
                        'tournament_id' => $tournament->id,
                        'invited_club_id' => $invitedId,
                        'status' => 'pending',
                        'invited_at' => now(),
                    ]);

                    // Audit log for invitation creation
                    AuditLogger::log(
                        actorId: $club->id,
                        action: 'create_tournament_invitation',
                        entityType: Tournament::class,
                        entityId: $tournament->id,
                        before: null,
                        after: [
                            'tournament_id' => $tournament->id,
                            'tournament_name' => $tournament->name,
                            'status' => 'pending',
                            'opponent_club_id' => $invitedId,
                        ]
                    );

                    // Notify opponent club
                    $opponent = User::find($invitedId);
                    if ($opponent) {
                        $opponent->notify(new TournamentInvitationNotification($tournament));
                    }
                }

                // 2. Submit the host club team transactionally
                if (isset($data['host_team_player_ids']) && is_array($data['host_team_player_ids'])) {
                    // Create tournament team for host
                    $team = TournamentTeam::create([
                        'tournament_id' => $tournament->id,
                        'club_id' => $club->id,
                        'submission_status' => 'submitted',
                        'submitted_at' => now(),
                    ]);

                    foreach ($data['host_team_player_ids'] as $index => $pid) {
                        // Validate eligibility of player
                        $player = User::find($pid);
                        if (! $player) {
                            $this->apiError("Player ID {$pid} is not found.", 'PLAYER_NOT_FOUND', 422);
                        }
                        if (! $this->validatePlayerEligibility($player, $club, $tournament)) {
                            $this->apiError("Player ID {$pid} is not eligible for this tournament.", 'PLAYER_NOT_ELIGIBLE', 422);
                        }

                        // Save in tournament_team_players
                        TournamentTeamPlayer::create([
                            'team_id' => $team->id,
                            'player_id' => $pid,
                            'position' => $index + 1,
                        ]);

                        // Save in legacy registrations table to keep legacy counts & assertions happy
                        TournamentRegistration::create([
                            'tournament_id' => $tournament->id,
                            'player_id' => $pid,
                            'payment_method_id' => 'club_submission',
                            'payment_status' => 'paid',
                            'registration_status' => 'registered',
                            'amount' => $tournament->entry_fees ?? 0,
                            'currency' => 'PKR',
                        ]);
                    }

                    // Update registered count
                    $tournament->registered_players_count = TournamentRegistration::where('tournament_id', $tournament->id)
                        ->where('registration_status', 'registered')
                        ->count();
                    $tournament->save();
                }
            } else {
                // CLUB_MEMBERS_ONLY
                // Notify eligible club members
                $this->notifyEligibleClubMembers($club, $tournament);
            }

            return $tournament;
        });
    }

    private function notifyEligibleClubMembers(User $club, Tournament $tournament): void
    {
        // Extract age range from age_group, e.g. "15-25"
        $ageRange = explode('-', (string) $tournament->age_group);
        $minAge = isset($ageRange[0]) ? (int) $ageRange[0] : null;
        $maxAge = isset($ageRange[1]) ? (int) $ageRange[1] : null;

        // Query approved player memberships for this organizing club
        $memberships = ClubMembership::query()
            ->with('player')
            ->where('club_id', $club->id)
            ->where('status', ClubMembership::STATUS_APPROVED)
            ->get();

        foreach ($memberships as $membership) {
            $player = $membership->player;
            if (! $player || $player->status !== 'active') {
                continue;
            }

            // 1. Gender check
            if ($tournament->gender !== 'OPEN' && $tournament->gender !== 'MIXED') {
                if (strcasecmp((string) $player->gender, (string) $tournament->gender) !== 0) {
                    continue;
                }
            }

            // 2. Level check
            if ($tournament->player_level && is_array($tournament->player_level)) {
                $playerLevel = strtoupper((string) $player->playing_level);
                $allowedLevels = array_map('strtoupper', $tournament->player_level);
                if (! in_array($playerLevel, $allowedLevels, true)) {
                    continue;
                }
            }

            // 3. Age check
            if ($minAge !== null && $maxAge !== null && $player->dob) {
                // Calculate age
                $age = $player->dob->age;
                if ($age < $minAge || $age > $maxAge) {
                    continue;
                }
            }

            // Player is eligible! Notify them.
            $player->notify(new TournamentAvailableNotification($tournament));
        }
    }

    public function updateTournament(User $club, string $tournamentId, array $data, ?UploadedFile $imageFile = null): Tournament
    {
        return DB::transaction(function () use ($club, $tournamentId, $data, $imageFile) {
            $tournament = $this->findClubTournament($club, $tournamentId);

            if (array_key_exists('name', $data)) {
                $tournament->name = $data['name'];
            }

            if (array_key_exists('format', $data)) {
                $tournament->format = $data['format'];
            }

            if (array_key_exists('start_date', $data)) {
                $tournament->start_date = $data['start_date'];
            }

            if (array_key_exists('registration_deadline', $data)) {
                $tournament->registration_deadline = $data['registration_deadline'];
            }

            if (array_key_exists('end_date', $data)) {
                $tournament->end_date = $data['end_date'];
            }

            if (array_key_exists('entry_fees', $data)) {
                $tournament->entry_fees = $data['entry_fees'];
            }

            if (array_key_exists('prize_pool', $data)) {
                $tournament->prize_pool = $data['prize_pool'];
            }

            if (array_key_exists('allowed_player', $data)) {
                $tournament->allowed_player = $data['allowed_player'];
            }

            if (array_key_exists('rules', $data)) {
                $tournament->rules = $data['rules'];
            }

            if ($imageFile) {
                $this->deleteStoredTournamentImage($tournament->tournament_image);
                $tournament->tournament_image = $this->storeUploadedTournamentImage($imageFile);
            } elseif (array_key_exists('tournament_image', $data) && is_string($data['tournament_image'])) {
                $tournament->tournament_image = $data['tournament_image'];
            }

            $tournament->save();

            return $tournament->refresh();
        });
    }

    private function findClubCourt(User $club, string $courtId): Court
    {
        if (! ctype_digit($courtId)) {
            throw (new ModelNotFoundException)->setModel(Court::class, [$courtId]);
        }

        $court = $club->courts()->whereKey((int) $courtId)->first();

        if (! $court) {
            throw (new ModelNotFoundException)->setModel(Court::class, [$courtId]);
        }

        return $court;
    }

    private function findClubTournament(User $club, string $tournamentId): Tournament
    {
        if (! is_numeric($tournamentId)) {
            $this->apiError('Tournament not found.', 'NOT_FOUND', 404);
        }

        $tournament = Tournament::find((int) $tournamentId);

        if (! $tournament) {
            $this->apiError('Tournament not found.', 'NOT_FOUND', 404);
        }

        return $tournament;
    }

    private function findClubBooking(User $club, string $bookingId): Booking
    {
        if (! ctype_digit($bookingId)) {
            throw (new ModelNotFoundException)->setModel(Booking::class, [$bookingId]);
        }

        $booking = $club->bookingsAsClub()
            ->with(['player', 'court'])
            ->whereKey((int) $bookingId)
            ->first();

        if (! $booking) {
            throw (new ModelNotFoundException)->setModel(Booking::class, [$bookingId]);
        }

        return $booking;
    }

    private function storeTournamentImage(?UploadedFile $file, mixed $existingValue): ?string
    {
        if ($file) {
            return $this->storeUploadedTournamentImage($file);
        }

        if (is_string($existingValue) && $existingValue !== '') {
            return $existingValue;
        }

        return null;
    }

    private function storeUploadedTournamentImage(UploadedFile $file): string
    {
        return $file->store('tournament-images', 'public');
    }

    private function storeUploadedClubLogo(UploadedFile $file): string
    {
        return $file->store('club-logos', 'public');
    }

    private function deleteStoredClubLogo(?string $value): void
    {
        if (! $value) {
            return;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return;
        }

        Storage::disk('public')->delete($value);
    }

    private function deleteStoredTournamentImage(?string $value): void
    {
        if (! $value) {
            return;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return;
        }

        Storage::disk('public')->delete($value);
    }

    private function notifyCityPlayersAboutTournament(User $club, Tournament $tournament): void
    {
        if (! $club->city_id && ! $club->city) {
            return;
        }

        User::query()
            ->where('role', 'player')
            ->where('status', 'active')
            ->when($club->city_id, fn ($query) => $query->where('city_id', $club->city_id))
            ->when(! $club->city_id && $club->city, fn ($query) => $query->where('city', $club->city))
            ->chunkById(100, function ($players) use ($tournament) {
                $players->each(
                    fn (User $player) => $player->notify((new TournamentCreatedNotification($tournament))->afterCommit())
                );
            });
    }

    private function notifyCityPlayersAboutCourtCreated(User $club, Court $court): void
    {
        if (! $club->city_id && ! $club->city) {
            return;
        }

        User::query()
            ->where('role', 'player')
            ->where('status', 'active')
            ->when($club->city_id, fn ($query) => $query->where('city_id', $club->city_id))
            ->when(! $club->city_id && $club->city, fn ($query) => $query->where('city', $club->city))
            ->chunkById(100, function ($players) use ($court) {
                $players->each(
                    fn (User $player) => $player->notify((new CourtCreatedNotification($court))->afterCommit())
                );
            });
    }

    private function notifyCityPlayersAboutCourtMaintenance(User $club, Court $court): void
    {
        if (! $club->city_id && ! $club->city) {
            return;
        }

        User::query()
            ->where('role', 'player')
            ->where('status', 'active')
            ->when($club->city_id, fn ($query) => $query->where('city_id', $club->city_id))
            ->when(! $club->city_id && $club->city, fn ($query) => $query->where('city', $club->city))
            ->chunkById(100, function ($players) use ($court) {
                $players->each(
                    fn (User $player) => $player->notify((new CourtMaintenanceNotification($court))->afterCommit())
                );
            });
    }

    private function mapInputStatusToStorage(string $status): string
    {
        return $status === 'maintenance' ? 'maintenance' : 'active';
    }

    private function normalizeFacilities(mixed $facilities): array
    {
        if (is_array($facilities)) {
            return array_values(array_filter($facilities, fn ($item) => is_string($item) && trim($item) !== ''));
        }

        if (is_string($facilities)) {
            $decoded = json_decode($facilities, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->normalizeFacilities($decoded);
            }

            return array_values(array_filter(array_map('trim', explode(',', $facilities))));
        }

        return [];
    }

    private function countClubBookings(int $clubId, bool $todayOnly = false, ?string $status = null): int
    {
        $query = DB::table('bookings')->where('club_id', $clubId);

        if ($todayOnly && Schema::hasColumn('bookings', 'booking_date')) {
            $query->whereDate('booking_date', now()->toDateString());
        }

        if ($status !== null && Schema::hasColumn('bookings', 'booking_status')) {
            $query->where('booking_status', $status);
        }

        return (int) $query->count();
    }

    private function countClubTournaments(int $clubId): int
    {
        if (! Schema::hasTable('tournaments')) {
            return 0;
        }

        $query = DB::table('tournaments')->where('club_id', $clubId);

        if (Schema::hasColumn('tournaments', 'status')) {
            $query->where('status', 'open');
        }

        return (int) $query->count();
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

    public function respondToInvitation(User $club, string $tournamentId, string $decision): string
    {
        $decision = strtoupper($decision);
        $tournament = Tournament::find($tournamentId);
        if (! $tournament) {
            $this->apiError('Tournament not found.', 'NOT_FOUND', 404);
        }

        // Verify that the authenticated club is the invited club
        $opponentIds = array_map('intval', (array) ($tournament->opponent_club_id ?? []));
        if (! in_array((int) $club->id, $opponentIds, true)) {
            $this->apiError('You are not authorized to respond to this invitation.', 'FORBIDDEN', 403);
        }

        // Get invitation record for the authenticated club
        $invitation = TournamentInvitation::where('tournament_id', $tournament->id)
            ->where('invited_club_id', $club->id)
            ->first();

        // If invitation exists and status is not pending, it means they have already responded
        if ($invitation && $invitation->status !== 'pending') {
            $this->apiError('Invitation response already finalized.', 'ALREADY_RESPONDED', 409);
        }

        $newStatus = $tournament->status; // Default to current status
        $updatedOpponentIds = $opponentIds;

        if ($decision === 'ACCEPT') {
            $newStatus = 'soft_accepted';
        } else {
            // It's a rejection: Remove the rejecting club ID from tournament's opponent_club_id list
            $updatedOpponentIds = array_values(array_filter($opponentIds, fn ($id) => (int) $id !== (int) $club->id));

            // Get other invitations
            $otherInvitations = TournamentInvitation::where('tournament_id', $tournament->id)
                ->where('invited_club_id', '!=', $club->id)
                ->get();

            if (empty($updatedOpponentIds)) {
                $newStatus = 'rejected';
            } else {
                $anyOtherAccepted = false;
                foreach ($otherInvitations as $otherInv) {
                    if ($otherInv->status === 'accepted') {
                        $anyOtherAccepted = true;
                        break;
                    }
                }
                $newStatus = $anyOtherAccepted ? 'soft_accepted' : 'pending';
            }
        }

        $beforeStatus = $tournament->status;

        DB::transaction(function () use ($tournament, $club, $newStatus, $decision, $beforeStatus, $updatedOpponentIds) {
            $tournament->status = $newStatus;
            $tournament->opponent_club_id = $updatedOpponentIds;
            $tournament->save();

            TournamentInvitation::updateOrCreate(
                [
                    'tournament_id' => $tournament->id,
                    'invited_club_id' => $club->id,
                ],
                [
                    'status' => $decision === 'ACCEPT' ? 'accepted' : 'rejected',
                    'responded_at' => now(),
                ]
            );

            // Log action in AuditLogger
            AuditLogger::log(
                actorId: $club->id,
                action: strtolower($decision).'_tournament_invitation',
                entityType: Tournament::class,
                entityId: $tournament->id,
                before: ['status' => $beforeStatus],
                after: ['status' => $newStatus, 'opponent_club_id' => $updatedOpponentIds]
            );

            // Notify organizing club
            $organizer = $tournament->club;
            if ($organizer) {
                if ($decision === 'ACCEPT') {
                    $organizer->notify(new TournamentInvitationAcceptedNotification($tournament, $club));
                } else {
                    $organizer->notify(new TournamentInvitationRejectedNotification($tournament, $club));
                }
            }
        });

        return $newStatus;
    }

    public function validatePlayerEligibility(User $player, User $club, Tournament $tournament): bool
    {
        // 1. Must belong to the submitting club
        $membership = ClubMembership::where('club_id', $club->id)
            ->where('player_id', $player->id)
            ->where('status', ClubMembership::STATUS_APPROVED)
            ->first();

        if (! $membership) {
            return false;
        }

        // 2. Gender check
        if ($tournament->gender && $tournament->gender !== 'OPEN' && $tournament->gender !== 'MIXED') {
            if (strtolower($player->gender) !== strtolower($tournament->gender)) {
                return false;
            }
        }

        // 3. Level check
        if ($tournament->player_level && is_array($tournament->player_level)) {
            $allowedLevels = array_map('strtolower', $tournament->player_level);
            if (in_array('professional', $allowedLevels, true) && ! in_array('advanced', $allowedLevels, true)) {
                $allowedLevels[] = 'advanced';
            }
            if (! in_array(strtolower($player->playing_level), $allowedLevels, true)) {
                return false;
            }
        }

        // 4. Age check
        if ($tournament->age_group) {
            $ageRange = explode('-', (string) $tournament->age_group);
            $minAge = isset($ageRange[0]) ? (int) $ageRange[0] : null;
            $maxAge = isset($ageRange[1]) ? (int) $ageRange[1] : null;

            if ($minAge !== null && $maxAge !== null) {
                if (! $player->dob) {
                    return false;
                }
                $age = $player->dob->age;
                if ($age < $minAge || $age > $maxAge) {
                    return false;
                }
            }
        }

        return true;
    }

    public function eligiblePlayers(User $club, string $tournamentId, int $page = 1, int $limit = 20, ?string $search = null): array
    {
        $tournament = Tournament::find($tournamentId);
        if (! $tournament) {
            $this->apiError('Tournament not found.', 'NOT_FOUND', 404);
        }

        // Verify that the authenticated club is the invited club, organizing club, or in invitations
        $isOrganizer = (int) $tournament->club_id === $club->id;
        $isInvited = TournamentInvitation::where('tournament_id', $tournament->id)
            ->where('invited_club_id', $club->id)
            ->exists();

        $opponentIds = (array) ($tournament->opponent_club_id ?? []);
        if (! $isOrganizer && ! $isInvited && ! in_array((int) $club->id, array_map('intval', $opponentIds), true)) {
            $this->apiError('You are not authorized to view eligible players for this tournament.', 'FORBIDDEN', 403);
        }

        $targetClubId = $club->id;

        // Extract age range from age_group
        $ageRange = explode('-', (string) $tournament->age_group);
        $minAge = isset($ageRange[0]) ? (int) $ageRange[0] : null;
        $maxAge = isset($ageRange[1]) ? (int) $ageRange[1] : null;

        $query = User::query()
            ->where('role', 'player')
            ->where('status', 'active')
            ->whereExists(function ($q) use ($targetClubId) {
                $q->select(DB::raw(1))
                    ->from('club_memberships')
                    ->whereColumn('club_memberships.player_id', 'users.id')
                    ->where('club_memberships.club_id', $targetClubId)
                    ->where('club_memberships.status', 'approved');
            });

        // 1. Gender check
        if ($tournament->gender !== 'OPEN' && $tournament->gender !== 'MIXED') {
            $query->whereRaw('LOWER(gender) = ?', [strtolower($tournament->gender)]);
        }

        // 2. Level check
        if ($tournament->player_level && is_array($tournament->player_level)) {
            $allowedLevels = array_map('strtolower', $tournament->player_level);
            if (in_array('professional', $allowedLevels, true) && ! in_array('advanced', $allowedLevels, true)) {
                $allowedLevels[] = 'advanced';
            }
            $query->whereIn(DB::raw('LOWER(playing_level)'), $allowedLevels);
        }

        // 3. Age check
        if ($minAge !== null && $maxAge !== null) {
            $query->whereNotNull('dob');
            if (DB::connection()->getDriverName() === 'sqlite') {
                $query->whereRaw("cast(strftime('%Y', 'now') - strftime('%Y', dob) as integer) BETWEEN ? AND ?", [$minAge, $maxAge]);
            } else {
                $query->whereRaw('TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN ? AND ?', [$minAge, $maxAge]);
            }
        }

        // Search check (by name or membership number)
        if ($search) {
            $query->where(function ($q) use ($search, $targetClubId) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereExists(function ($sub) use ($search, $targetClubId) {
                        $sub->select(DB::raw(1))
                            ->from('club_memberships')
                            ->whereColumn('club_memberships.player_id', 'users.id')
                            ->where('club_memberships.club_id', $targetClubId)
                            ->where('club_memberships.membership_number', 'like', "%{$search}%");
                    });
            });
        }

        // Paginate
        $paginator = $query->paginate($limit, ['*'], 'page', $page);

        // Map results to the required format
        $data = collect($paginator->items())->map(function ($player) use ($targetClubId) {
            $membership = ClubMembership::where('club_id', $targetClubId)
                ->where('player_id', $player->id)
                ->first();

            return [
                'player_id' => $player->id,
                'full_name' => $player->name,
                'profile_image' => app_image_url($player->profile_image),
                'membership_number' => $membership?->membership_number,
                'gender' => $player->gender,
                'age' => $player->dob ? $player->dob->age : null,
                'level' => $player->playing_level,
                'membership_status' => $membership?->status,
            ];
        })->toArray();

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_next' => $paginator->hasMorePages(),
                'has_previous' => $paginator->currentPage() > 1,
            ],
        ];
    }

    public function submitTeam(User $club, string $tournamentId, array $playerIds): string
    {
        $tournament = Tournament::find($tournamentId);
        if (! $tournament) {
            $this->apiError('Tournament not found.', 'NOT_FOUND', 404);
        }

        // Verify authorization: organizing club, opponent club, or in invitations
        $isOrganizer = (int) $tournament->club_id === $club->id;
        $isInvited = TournamentInvitation::where('tournament_id', $tournament->id)
            ->where('invited_club_id', $club->id)
            ->where('status', 'accepted')
            ->exists();

        // Fallback for legacy single opponent: if no invitation record exists, check opponent_club_id
        $isLegacyOpponent = false;
        if (! $isInvited) {
            $hasInvitationRecord = TournamentInvitation::where('tournament_id', $tournament->id)
                ->where('invited_club_id', $club->id)
                ->exists();
            $opponentIds = (array) ($tournament->opponent_club_id ?? []);
            if (! $hasInvitationRecord && in_array((int) $club->id, array_map('intval', $opponentIds), true)) {
                $isLegacyOpponent = true;
            }
        }

        if (! $isOrganizer && ! $isInvited && ! $isLegacyOpponent) {
            $this->apiError('You are not authorized to submit a team for this tournament.', 'FORBIDDEN', 403);
        }

        // Verify registration deadline has not passed
        if ($tournament->registration_deadline && now()->greaterThan($tournament->registration_deadline)) {
            $this->apiError('Registration deadline has passed.', 'REGISTRATION_CLOSED', 410);
        }

        // Verify capacity is not exceeded
        $maxPlayers = (int) $tournament->maximum_players;
        if (count($playerIds) > $maxPlayers) {
            $this->apiError('Selected roster size exceeds maximum players allowed.', 'CAPACITY_REACHED', 409);
        }

        // Verify duplicate player IDs in request
        if (count($playerIds) !== count(array_unique($playerIds))) {
            $this->apiError('Duplicate player IDs are not allowed in the team roster.', 'DUPLICATE_PLAYERS', 422);
        }

        // Verify each player exists and is eligible
        foreach ($playerIds as $pid) {
            $player = User::find($pid);
            if (! $player) {
                $this->apiError("Player ID {$pid} is not found.", 'PLAYER_NOT_FOUND', 422);
            }
            if (! $this->validatePlayerEligibility($player, $club, $tournament)) {
                $this->apiError("Player ID {$pid} is not eligible for this tournament.", 'PLAYER_NOT_ELIGIBLE', 422);
            }
        }

        DB::transaction(function () use ($tournament, $club, $playerIds) {
            // Find or create TournamentTeam
            $team = TournamentTeam::updateOrCreate(
                [
                    'tournament_id' => $tournament->id,
                    'club_id' => $club->id,
                ],
                [
                    'submission_status' => 'submitted',
                    'submitted_at' => now(),
                ]
            );

            // Delete old team players
            TournamentTeamPlayer::where('team_id', $team->id)->delete();

            // Insert new team players with sequence positions
            foreach ($playerIds as $index => $pid) {
                TournamentTeamPlayer::create([
                    'team_id' => $team->id,
                    'player_id' => $pid,
                    'position' => $index + 1,
                ]);
            }

            // Sync with legacy TournamentRegistration table:
            // Find all current registrations for this tournament and players belonging to this club
            $clubPlayerIds = User::where('role', 'player')
                ->whereExists(function ($q) use ($club) {
                    $q->select(DB::raw(1))
                        ->from('club_memberships')
                        ->whereColumn('club_memberships.player_id', 'users.id')
                        ->where('club_memberships.club_id', $club->id);
                })
                ->pluck('id')
                ->toArray();

            // Delete legacy registrations for this club's players who are not in the new roster
            TournamentRegistration::where('tournament_id', $tournament->id)
                ->whereIn('player_id', $clubPlayerIds)
                ->whereNotIn('player_id', $playerIds)
                ->delete();

            // Insert or update legacy registrations for the new roster
            foreach ($playerIds as $pid) {
                TournamentRegistration::updateOrCreate(
                    [
                        'tournament_id' => $tournament->id,
                        'player_id' => $pid,
                    ],
                    [
                        'payment_method_id' => 'club_submission',
                        'payment_status' => 'paid',
                        'registration_status' => 'registered',
                        'amount' => $tournament->entry_fees ?? 0,
                        'currency' => 'PKR',
                    ]
                );
            }

            // Update registered count
            $tournament->registered_players_count = TournamentRegistration::where('tournament_id', $tournament->id)
                ->where('registration_status', 'registered')
                ->count();

            // Set tournament status to confirmed (accepted/confirmed per status model)
            $tournament->status = 'confirmed';
            $tournament->save();

            // Audit log
            AuditLogger::log(
                actorId: $club->id,
                action: 'submit_tournament_team',
                entityType: Tournament::class,
                entityId: $tournament->id,
                before: null,
                after: [
                    'status' => 'confirmed',
                    'player_ids' => $playerIds,
                ]
            );

            // Notify organizer club
            $organizer = $tournament->club;
            if ($organizer && (int) $organizer->id !== $club->id) {
                $organizer->notify(new TournamentTeamSubmittedNotification($tournament));
            }
        });

        return 'confirmed';
    }

    public function getTournamentTeam(User $club, string $tournamentId): array
    {
        $tournament = Tournament::find($tournamentId);
        if (! $tournament) {
            $this->apiError('Tournament not found.', 'NOT_FOUND', 404);
        }

        // Verify authorization: organizing club, opponent club, or in invitations
        $isOrganizer = (int) $tournament->club_id === $club->id;
        $isInvited = TournamentInvitation::where('tournament_id', $tournament->id)
            ->where('invited_club_id', $club->id)
            ->exists();

        $opponentIds = (array) ($tournament->opponent_club_id ?? []);
        if (! $isOrganizer && ! $isInvited && ! in_array((int) $club->id, array_map('intval', $opponentIds), true)) {
            $this->apiError('You are not authorized to view the team roster for this tournament.', 'FORBIDDEN', 403);
        }

        // 1. Build legacy flat array of players (from tournament_registrations)
        $registrations = TournamentRegistration::where('tournament_id', $tournament->id)
            ->with(['player'])
            ->get();

        $legacyFlatPlayers = $registrations->map(function ($reg) use ($tournament, $opponentIds) {
            $player = $reg->player;
            if (! $player) {
                return null;
            }

            $possibleClubIds = array_merge([$tournament->club_id], array_map('intval', $opponentIds));
            $membership = ClubMembership::whereIn('club_id', $possibleClubIds)
                ->where('player_id', $player->id)
                ->where('status', 'approved')
                ->first();

            return [
                'player_id' => $player->id,
                'full_name' => $player->name,
                'profile_image' => app_image_url($player->profile_image),
                'membership_number' => $membership?->membership_number,
                'gender' => $player->gender,
                'age' => $player->dob ? $player->dob->age : null,
                'level' => $player->playing_level,
                'membership_status' => $membership?->status,
                'registration_status' => $reg->registration_status,
                'payment_status' => $reg->payment_status,
                'amount' => $reg->amount,
                'currency' => $reg->currency,
            ];
        })->filter()->values()->toArray();

        // 2. Build the new day-wise/grouped teams array
        $teamsGrouped = [];

        // Add Host Club Team
        $hostClub = $tournament->club;
        if ($hostClub) {
            $hostTeam = TournamentTeam::where('tournament_id', $tournament->id)
                ->where('club_id', $hostClub->id)
                ->first();

            $hostPlayers = [];
            if ($hostTeam) {
                $teamPlayers = TournamentTeamPlayer::where('team_id', $hostTeam->id)
                    ->with('player')
                    ->orderBy('position', 'asc')
                    ->get();

                foreach ($teamPlayers as $tp) {
                    if ($tp->player) {
                        $membership = ClubMembership::where('club_id', $hostClub->id)
                            ->where('player_id', $tp->player->id)
                            ->first();

                        $hostPlayers[] = [
                            'player_id' => $tp->player->id,
                            'full_name' => $tp->player->name,
                            'profile_image' => app_image_url($tp->player->profile_image),
                            'membership_number' => $membership?->membership_number,
                            'gender' => $tp->player->gender,
                            'age' => $tp->player->dob ? $tp->player->dob->age : null,
                            'level' => $tp->player->playing_level,
                            'position' => $tp->position,
                        ];
                    }
                }
            }

            $teamsGrouped[] = [
                'club_id' => $hostClub->id,
                'club_name' => $hostClub->club_name ?? $hostClub->name,
                'invitation_status' => 'host',
                'team_status' => ! empty($hostPlayers) ? 'submitted' : 'not_submitted',
                'players' => $hostPlayers,
            ];
        }

        // Get invited clubs (excluding rejected invitations)
        $invitations = TournamentInvitation::where('tournament_id', $tournament->id)
            ->where('status', '!=', 'rejected')
            ->with('invitedClub')
            ->get();

        $invitedClubIds = [];
        foreach ($invitations as $inv) {
            $invitedClub = $inv->invitedClub;
            if (! $invitedClub) {
                continue;
            }
            if ($inv->status === 'rejected') {
                continue;
            }
            $invitedClubIds[] = $invitedClub->id;

            $oppTeam = TournamentTeam::where('tournament_id', $tournament->id)
                ->where('club_id', $invitedClub->id)
                ->first();

            $oppPlayers = [];
            if ($oppTeam) {
                $teamPlayers = TournamentTeamPlayer::where('team_id', $oppTeam->id)
                    ->with('player')
                    ->orderBy('position', 'asc')
                    ->get();

                foreach ($teamPlayers as $tp) {
                    if ($tp->player) {
                        $membership = ClubMembership::where('club_id', $invitedClub->id)
                            ->where('player_id', $tp->player->id)
                            ->first();

                        $oppPlayers[] = [
                            'player_id' => $tp->player->id,
                            'full_name' => $tp->player->name,
                            'profile_image' => app_image_url($tp->player->profile_image),
                            'membership_number' => $membership?->membership_number,
                            'gender' => $tp->player->gender,
                            'age' => $tp->player->dob ? $tp->player->dob->age : null,
                            'level' => $tp->player->playing_level,
                            'position' => $tp->position,
                        ];
                    }
                }
            }

            $teamsGrouped[] = [
                'club_id' => $invitedClub->id,
                'club_name' => $invitedClub->club_name ?? $invitedClub->name,
                'invitation_status' => $inv->status,
                'team_status' => ! empty($oppPlayers) ? 'submitted' : 'not_submitted',
                'players' => $oppPlayers,
            ];
        }

        // Support legacy opponent_club_id if not already in invitations
        $opponentClubIds = (array) ($tournament->opponent_club_id ?? []);
        foreach ($opponentClubIds as $oppId) {
            $oppId = (int) $oppId;
            if ($oppId && ! in_array($oppId, $invitedClubIds, true)) {
                $oppClub = User::find($oppId);
                if ($oppClub) {
                    $oppTeam = TournamentTeam::where('tournament_id', $tournament->id)
                        ->where('club_id', $oppClub->id)
                        ->first();

                    $oppPlayers = [];
                    if ($oppTeam) {
                        $teamPlayers = TournamentTeamPlayer::where('team_id', $oppTeam->id)
                            ->with('player')
                            ->orderBy('position', 'asc')
                            ->get();

                        foreach ($teamPlayers as $tp) {
                            if ($tp->player) {
                                $membership = ClubMembership::where('club_id', $oppClub->id)
                                    ->where('player_id', $tp->player->id)
                                    ->first();

                                $oppPlayers[] = [
                                    'player_id' => $tp->player->id,
                                    'full_name' => $tp->player->name,
                                    'profile_image' => app_image_url($tp->player->profile_image),
                                    'membership_number' => $membership?->membership_number,
                                    'gender' => $tp->player->gender,
                                    'age' => $tp->player->dob ? $tp->player->dob->age : null,
                                    'level' => $tp->player->playing_level,
                                    'position' => $tp->position,
                                ];
                            }
                        }
                    } else {
                        $oppRegs = TournamentRegistration::where('tournament_id', $tournament->id)
                            ->whereHas('player', function ($q) use ($oppClub) {
                                $q->whereExists(function ($sub) use ($oppClub) {
                                    $sub->select(DB::raw(1))
                                        ->from('club_memberships')
                                        ->whereColumn('club_memberships.player_id', 'users.id')
                                        ->where('club_memberships.club_id', $oppClub->id)
                                        ->where('club_memberships.status', 'approved');
                                });
                            })
                            ->with(['player'])
                            ->get();

                        foreach ($oppRegs as $index => $reg) {
                            if ($reg->player) {
                                $membership = ClubMembership::where('club_id', $oppClub->id)
                                    ->where('player_id', $reg->player->id)
                                    ->first();

                                $oppPlayers[] = [
                                    'player_id' => $reg->player->id,
                                    'full_name' => $reg->player->name,
                                    'profile_image' => app_image_url($reg->player->profile_image),
                                    'membership_number' => $membership?->membership_number,
                                    'gender' => $reg->player->gender,
                                    'age' => $reg->player->dob ? $reg->player->dob->age : null,
                                    'level' => $reg->player->playing_level,
                                    'position' => $index + 1,
                                ];
                            }
                        }
                    }

                    $invStatus = $tournament->status === 'confirmed' ? 'accepted' : 'pending';
                    $teamsGrouped[] = [
                        'club_id' => $oppClub->id,
                        'club_name' => $oppClub->club_name ?? $oppClub->name,
                        'invitation_status' => $invStatus,
                        'team_status' => ! empty($oppPlayers) ? 'submitted' : 'not_submitted',
                        'players' => $oppPlayers,
                    ];
                }
            }
        }

        return [
            'legacy_flat' => $legacyFlatPlayers,
            'teams_grouped' => $teamsGrouped,
        ];
    }

    public function acceptRegistration(User $club, int $tournamentId, int $registrationId): TournamentRegistration
    {
        return DB::transaction(function () use ($club, $tournamentId, $registrationId) {
            $tournament = Tournament::query()
                ->whereKey($tournamentId)
                ->first();

            if (! $tournament) {
                $this->apiError('Tournament does not exist.', ApiErrorCode::RECORD_NOT_FOUND, 404);
            }

            // Verify authorization: only the organizing club can approve registrations
            if ((int) $tournament->club_id !== (int) $club->id) {
                $this->apiError('You are not authorized to accept registrations for this tournament.', 'UNAUTHORIZED_ACCESS', 403);
            }

            $registration = TournamentRegistration::where('tournament_id', $tournament->id)
                ->whereKey($registrationId)
                ->lockForUpdate()
                ->first();

            if (! $registration) {
                $this->apiError('Registration not found.', ApiErrorCode::RECORD_NOT_FOUND, 404);
            }

            if ($registration->registration_status !== 'pending') {
                $this->apiError('Only pending registrations can be accepted.', ApiErrorCode::VALIDATION_ERROR, 400);
            }

            $registration->update([
                'registration_status' => 'accepted',
            ]);

            return $registration;
        });
    }

    public function getClubOfficials(User $club): array
    {
        $scorers = User::where('role', 'player')
            ->where('status', 'active')
            ->where('are_you_scorer', true)
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'full_name' => $u->name,
                    'email' => $u->email,
                    'phone' => $u->phone,
                    'profile_image_url' => app_image_url($u->profile_image),
                    'playing_level' => strtolower($u->playing_level) === 'advanced' ? 'professional' : $u->playing_level,
                    'dob' => $u->dob?->toDateString(),
                    'gender' => $u->gender,
                ];
            })
            ->all();

        $umpires = User::where('role', 'player')
            ->where('status', 'active')
            ->where('are_you_umpire', true)
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'full_name' => $u->name,
                    'email' => $u->email,
                    'phone' => $u->phone,
                    'profile_image_url' => app_image_url($u->profile_image),
                    'playing_level' => strtolower($u->playing_level) === 'advanced' ? 'professional' : $u->playing_level,
                    'dob' => $u->dob?->toDateString(),
                    'gender' => $u->gender,
                ];
            })
            ->all();

        return [
            'scorers' => $scorers,
            'umpires' => $umpires,
        ];
    }

    public function storeFixtures(User $club, string $tournamentId, array $data): void
    {
        $tournament = Tournament::find($tournamentId);
        if (! $tournament) {
            $this->apiError('Tournament not found.', 'NOT_FOUND', 404);
        }

        if ($tournament->club_id !== $club->id) {
            $this->apiError('You are not authorized to store fixtures for this tournament.', 'FORBIDDEN', 403);
        }

        $format = $data['format'] ?? null;
        if (! $format || strtolower($format) !== strtolower($tournament->format ?? '')) {
            $this->apiError('Fixture format does not match tournament format.', 'VALIDATION_ERROR', 422);
        }

        // Extract participating clubs: Host + accepted opponents
        $participatingClubIds = [$tournament->club_id];
        $acceptedInvites = TournamentInvitation::where('tournament_id', $tournament->id)
            ->whereIn('status', ['accepted', 'soft_accepted'])
            ->pluck('invited_club_id')
            ->toArray();
        $participatingClubIds = array_unique(array_map('intval', array_merge($participatingClubIds, $acceptedInvites)));

        // Extract submitted roster (team players) per club
        $teams = TournamentTeam::where('tournament_id', $tournament->id)->get();
        $rosters = [];
        foreach ($teams as $t) {
            $playerIds = TournamentTeamPlayer::where('team_id', $t->id)->pluck('player_id')->toArray();
            $rosters[(int) $t->club_id] = array_map('intval', $playerIds);
        }

        if (strtolower($format) === 'league') {
            $groupCount = $data['group_count'] ?? null;
            if ($groupCount === null || ! is_numeric($groupCount) || (int) $groupCount < 1) {
                $this->apiError('League tournament requires group_count >= 1.', 'VALIDATION_ERROR', 422);
            }

            $groupsPayload = $data['groups'] ?? [];
            if (! is_array($groupsPayload)) {
                $this->apiError('Groups payload must be an array.', 'VALIDATION_ERROR', 422);
            }

            $leagueGroups = array_filter($groupsPayload, function ($g) {
                return ! empty($g['club_ids']) || ! empty($g['player_ids']) || ($g['group_name'] ?? '') !== 'Knockout Stage';
            });

            if (count($leagueGroups) !== (int) $groupCount && count($groupsPayload) !== (int) $groupCount) {
                $this->apiError('Groups count does not match group_count.', 'VALIDATION_ERROR', 422);
            }

            if ((int) $groupCount === 1 && count($leagueGroups) === 1) {
                $firstGroup = reset($leagueGroups);
                if (($firstGroup['group_name'] ?? '') !== 'League') {
                    $this->apiError('Single league group name must be "League".', 'VALIDATION_ERROR', 422);
                }
            }

            $isClubMembersOnly = ($tournament->tournament_type === 'CLUB_MEMBERS_ONLY');

            if ($isClubMembersOnly) {
                $enrolledPlayerIds = TournamentRegistration::where('tournament_id', $tournament->id)
                    ->whereIn('registration_status', ['registered', 'accepted'])
                    ->pluck('player_id')
                    ->map(fn ($id) => (int) $id)
                    ->toArray();

                if (empty($enrolledPlayerIds)) {
                    $enrolledPlayerIds = TournamentRegistration::where('tournament_id', $tournament->id)
                        ->pluck('player_id')
                        ->map(fn ($id) => (int) $id)
                        ->toArray();
                }

                $allocatedPlayerIds = [];
                foreach ($groupsPayload as $groupPay) {
                    $grpPlayerIds = array_map('intval', (array) ($groupPay['player_ids'] ?? $groupPay['club_ids'] ?? []));
                    if (empty($grpPlayerIds) && ($groupPay['group_name'] ?? '') === 'Knockout Stage') {
                        continue;
                    }

                    foreach ($grpPlayerIds as $pId) {
                        if (! empty($enrolledPlayerIds) && ! in_array($pId, $enrolledPlayerIds, true)) {
                            $this->apiError("Player ID {$pId} is not an enrolled player in this tournament.", 'VALIDATION_ERROR', 422);
                        }
                        if (in_array($pId, $allocatedPlayerIds, true)) {
                            $this->apiError("Player ID {$pId} belongs to multiple groups.", 'VALIDATION_ERROR', 422);
                        }
                        $allocatedPlayerIds[] = $pId;
                    }
                }

                if (! empty($enrolledPlayerIds) && count($allocatedPlayerIds) !== count($enrolledPlayerIds)) {
                    $this->apiError('Every enrolled player must belong to exactly one group.', 'VALIDATION_ERROR', 422);
                }
            } else {
                $allocatedClubIds = [];
                foreach ($groupsPayload as $groupPay) {
                    $grpClubIds = array_map('intval', (array) ($groupPay['club_ids'] ?? []));
                    if (empty($grpClubIds) && ($groupPay['group_name'] ?? '') === 'Knockout Stage') {
                        continue;
                    }

                    foreach ($grpClubIds as $cId) {
                        if (! in_array($cId, $participatingClubIds, true)) {
                            $this->apiError("Club ID {$cId} is not a participating club in this tournament.", 'VALIDATION_ERROR', 422);
                        }
                        if (in_array($cId, $allocatedClubIds, true)) {
                            $this->apiError("Club ID {$cId} belongs to multiple groups.", 'VALIDATION_ERROR', 422);
                        }
                        $allocatedClubIds[] = $cId;
                    }
                }

                if (count($allocatedClubIds) !== count($participatingClubIds)) {
                    $this->apiError('Every participating club must belong to exactly one group.', 'VALIDATION_ERROR', 422);
                }
            }
        } else {
            if (isset($data['group_count']) && $data['group_count'] !== null) {
                $this->apiError('Knockout tournament must have null group_count.', 'VALIDATION_ERROR', 422);
            }
            if (isset($data['groups']) && ! empty($data['groups'])) {
                $this->apiError('Knockout tournament must have empty groups.', 'VALIDATION_ERROR', 422);
            }
        }

        // Collect and validate fixtures
        $allFixtures = [];
        if (strtolower($format) === 'league') {
            foreach (($data['groups'] ?? []) as $groupPay) {
                $fixturesPay = $groupPay['fixtures'] ?? [];
                if (! is_array($fixturesPay)) {
                    continue;
                }
                foreach ($fixturesPay as $fixPay) {
                    $allFixtures[] = [
                        'group_name' => $groupPay['group_name'],
                        'fixture' => $fixPay,
                    ];
                }
            }
        } else {
            $fixturesPay = $data['fixtures'] ?? [];
            if (is_array($fixturesPay)) {
                foreach ($fixturesPay as $fixPay) {
                    $allFixtures[] = [
                        'group_name' => null,
                        'fixture' => $fixPay,
                    ];
                }
            }
        }

        foreach ($allFixtures as $fixData) {
            $fix = $fixData['fixture'];
            $homeClubId = ! empty($fix['home_club_id']) ? (int) $fix['home_club_id'] : (! empty($fix['home_player_id']) ? (int) $fix['home_player_id'] : null);
            $awayClubId = ! empty($fix['away_club_id']) ? (int) $fix['away_club_id'] : (! empty($fix['away_player_id']) ? (int) $fix['away_player_id'] : null);
            $isBye = filter_var($fix['is_bye'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $byeClubId = ! empty($fix['bye_club_id']) ? (int) $fix['bye_club_id'] : (! empty($fix['bye_player_id']) ? (int) $fix['bye_player_id'] : (! empty($fix['home_player_id']) ? (int) $fix['home_player_id'] : null));
            $isRest = filter_var($fix['is_rest'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $restClubId = ! empty($fix['rest_club_id']) ? (int) $fix['rest_club_id'] : (! empty($fix['rest_player_id']) ? (int) $fix['rest_player_id'] : (! empty($fix['home_player_id']) ? (int) $fix['home_player_id'] : null));
            $round = $fix['round'] ?? null;

            if (! $round) {
                $this->apiError('Fixture is missing round details.', 'VALIDATION_ERROR', 422);
            }

            if ($homeClubId !== null) {
                if (! in_array($homeClubId, $participatingClubIds, true) && ! User::where('id', $homeClubId)->exists()) {
                    $this->apiError('Fixture club must be a participating club or valid user in this tournament.', 'VALIDATION_ERROR', 422);
                }
            }

            if ($isBye) {
                if ($awayClubId !== null && empty($fix['home_placeholder']) && empty($fix['away_placeholder'])) {
                    $this->apiError('Away club must be null for a bye fixture.', 'VALIDATION_ERROR', 422);
                }
                if ($byeClubId !== null && ! in_array($byeClubId, $participatingClubIds, true) && ! User::where('id', $byeClubId)->exists()) {
                    $this->apiError('Fixture club must be a participating club or valid user in this tournament.', 'VALIDATION_ERROR', 422);
                }
            } elseif ($isRest) {
                if ($awayClubId !== null && empty($fix['home_placeholder']) && empty($fix['away_placeholder'])) {
                    $this->apiError('Away club must be null for a rest fixture.', 'VALIDATION_ERROR', 422);
                }
                if ($restClubId !== null && ! in_array($restClubId, $participatingClubIds, true) && ! User::where('id', $restClubId)->exists()) {
                    $this->apiError('Fixture club must be a participating club or valid user in this tournament.', 'VALIDATION_ERROR', 422);
                }
            } else {
                if ($awayClubId !== null) {
                    if (! in_array($awayClubId, $participatingClubIds, true) && ! User::where('id', $awayClubId)->exists()) {
                        $this->apiError('Fixture club must be a participating club or valid user in this tournament.', 'VALIDATION_ERROR', 422);
                    }
                }
            }

            $matchesPay = $fix['matches'] ?? [];
            if ($isBye || $isRest) {
                if (! is_array($matchesPay) || ! empty($matchesPay)) {
                    $this->apiError('A bye or rest fixture must not contain any matches.', 'VALIDATION_ERROR', 422);
                }
            } else {
                if (! is_array($matchesPay)) {
                    $this->apiError('Matches details must be an array.', 'VALIDATION_ERROR', 422);
                }

                $sequences = [];
                foreach ($matchesPay as $matchPay) {
                    $seq = $matchPay['sequence'] ?? null;
                    $homePlayerId = isset($matchPay['home_player_id']) && $matchPay['home_player_id'] !== null && (int) $matchPay['home_player_id'] > 0 ? (int) $matchPay['home_player_id'] : null;
                    $awayPlayerId = isset($matchPay['away_player_id']) && $matchPay['away_player_id'] !== null && (int) $matchPay['away_player_id'] > 0 ? (int) $matchPay['away_player_id'] : null;

                    if ($seq === null || ! is_numeric($seq)) {
                        $this->apiError('Match sequence is missing or invalid.', 'VALIDATION_ERROR', 422);
                    }
                    if (in_array($seq, $sequences)) {
                        $this->apiError("Duplicate match sequence {$seq} found in fixture.", 'VALIDATION_ERROR', 422);
                    }
                    $sequences[] = $seq;

                    if ($homePlayerId !== null && $homeClubId !== null && isset($rosters[$homeClubId])) {
                        $homeRoster = $rosters[$homeClubId] ?? [];
                        if (! empty($homeRoster) && ! in_array($homePlayerId, $homeRoster, true)) {
                            $this->apiError("Player ID {$homePlayerId} does not belong to the submitted roster of club {$homeClubId}.", 'VALIDATION_ERROR', 422);
                        }
                    }
                    if ($awayPlayerId !== null && $awayClubId !== null && isset($rosters[$awayClubId])) {
                        $awayRoster = $rosters[$awayClubId] ?? [];
                        if (! empty($awayRoster) && ! in_array($awayPlayerId, $awayRoster, true)) {
                            $this->apiError("Player ID {$awayPlayerId} does not belong to the submitted roster of club {$awayClubId}.", 'VALIDATION_ERROR', 422);
                        }
                    }
                }
            }
        }

        DB::transaction(function () use ($tournament, $format, $data) {
            // Cascade on delete clean up
            TournamentGroup::where('tournament_id', $tournament->id)->delete();
            TournamentFixture::where('tournament_id', $tournament->id)->delete();

            if (strtolower($format) === 'league') {
                foreach (($data['groups'] ?? []) as $groupPay) {
                    $group = TournamentGroup::create([
                        'tournament_id' => $tournament->id,
                        'name' => $groupPay['group_name'],
                    ]);

                    $grpMembers = ! empty($groupPay['player_ids']) ? $groupPay['player_ids'] : ($groupPay['club_ids'] ?? []);
                    $group->clubs()->sync($grpMembers);

                    $fixturesPay = $groupPay['fixtures'] ?? [];
                    foreach ($fixturesPay as $fixPay) {
                        $fixture = TournamentFixture::create([
                            'tournament_id' => $tournament->id,
                            'group_id' => $group->id,
                            'round' => $fixPay['round'],
                            'home_club_id' => ! empty($fixPay['home_club_id']) ? (int) $fixPay['home_club_id'] : (! empty($fixPay['home_player_id']) ? (int) $fixPay['home_player_id'] : null),
                            'away_club_id' => ! empty($fixPay['away_club_id']) ? (int) $fixPay['away_club_id'] : (! empty($fixPay['away_player_id']) ? (int) $fixPay['away_player_id'] : null),
                            'home_placeholder' => $fixPay['home_placeholder'] ?? null,
                            'away_placeholder' => $fixPay['away_placeholder'] ?? null,
                            'is_bye' => filter_var($fixPay['is_bye'] ?? false, FILTER_VALIDATE_BOOLEAN),
                            'bye_club_id' => ! empty($fixPay['bye_club_id']) ? (int) $fixPay['bye_club_id'] : (! empty($fixPay['bye_player_id']) ? (int) $fixPay['bye_player_id'] : null),
                            'is_rest' => filter_var($fixPay['is_rest'] ?? false, FILTER_VALIDATE_BOOLEAN),
                            'rest_club_id' => ! empty($fixPay['rest_club_id']) ? (int) $fixPay['rest_club_id'] : (! empty($fixPay['rest_player_id']) ? (int) $fixPay['rest_player_id'] : null),
                            'court_id' => ! empty($fixPay['court_id']) ? (int) $fixPay['court_id'] : null,
                            'status' => filter_var($fixPay['is_bye'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'bye' : (filter_var($fixPay['is_rest'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'rest' : 'scheduled'),
                        ]);

                        $matchesPay = $fixPay['matches'] ?? [];
                        foreach ($matchesPay as $matchPay) {
                            $vId = ! empty($matchPay['venue_id']) ? (int) $matchPay['venue_id'] : null;
                            $cId = ! empty($matchPay['court_id']) ? (int) $matchPay['court_id'] : null;

                            $match = TournamentMatch::create([
                                'fixture_id' => $fixture->id,
                                'sequence' => $matchPay['sequence'],
                                'home_player_id' => ! empty($matchPay['home_player_id']) ? (int) $matchPay['home_player_id'] : null,
                                'away_player_id' => ! empty($matchPay['away_player_id']) ? (int) $matchPay['away_player_id'] : null,
                                'home_player_placeholder' => $matchPay['home_player_placeholder'] ?? null,
                                'away_player_placeholder' => $matchPay['away_player_placeholder'] ?? null,
                                'venue_id' => $vId,
                                'court_id' => $cId,
                                'start_date' => $matchPay['start_date'] ?? null,
                                'start_time' => $matchPay['start_time'] ?? null,
                                'status' => 'scheduled',
                            ]);

                            if (! empty($matchPay['scorer_ids']) && is_array($matchPay['scorer_ids'])) {
                                $match->scorers()->sync($matchPay['scorer_ids']);
                            }
                            if (! empty($matchPay['umpire_ids']) && is_array($matchPay['umpire_ids'])) {
                                $match->umpires()->sync($matchPay['umpire_ids']);
                            }
                        }
                    }
                }
            } else {
                $fixturesPay = $data['fixtures'] ?? [];
                foreach ($fixturesPay as $fixPay) {
                    $fixture = TournamentFixture::create([
                        'tournament_id' => $tournament->id,
                        'group_id' => null,
                        'round' => $fixPay['round'],
                        'home_club_id' => ! empty($fixPay['home_club_id']) ? (int) $fixPay['home_club_id'] : (! empty($fixPay['home_player_id']) ? (int) $fixPay['home_player_id'] : null),
                        'away_club_id' => ! empty($fixPay['away_club_id']) ? (int) $fixPay['away_club_id'] : (! empty($fixPay['away_player_id']) ? (int) $fixPay['away_player_id'] : null),
                        'home_placeholder' => $fixPay['home_placeholder'] ?? null,
                        'away_placeholder' => $fixPay['away_placeholder'] ?? null,
                        'is_bye' => filter_var($fixPay['is_bye'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'bye_club_id' => ! empty($fixPay['bye_club_id']) ? (int) $fixPay['bye_club_id'] : (! empty($fixPay['bye_player_id']) ? (int) $fixPay['bye_player_id'] : null),
                        'is_rest' => filter_var($fixPay['is_rest'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'rest_club_id' => ! empty($fixPay['rest_club_id']) ? (int) $fixPay['rest_club_id'] : (! empty($fixPay['rest_player_id']) ? (int) $fixPay['rest_player_id'] : null),
                        'court_id' => ! empty($fixPay['court_id']) ? (int) $fixPay['court_id'] : null,
                        'status' => filter_var($fixPay['is_bye'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'bye' : (filter_var($fixPay['is_rest'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'rest' : 'scheduled'),
                    ]);

                    $matchesPay = $fixPay['matches'] ?? [];
                    foreach ($matchesPay as $matchPay) {
                        $vId = ! empty($matchPay['venue_id']) ? (int) $matchPay['venue_id'] : null;
                        $cId = ! empty($matchPay['court_id']) ? (int) $matchPay['court_id'] : null;

                        $match = TournamentMatch::create([
                            'fixture_id' => $fixture->id,
                            'sequence' => $matchPay['sequence'],
                            'home_player_id' => ! empty($matchPay['home_player_id']) ? (int) $matchPay['home_player_id'] : null,
                            'away_player_id' => ! empty($matchPay['away_player_id']) ? (int) $matchPay['away_player_id'] : null,
                            'home_player_placeholder' => $matchPay['home_player_placeholder'] ?? null,
                            'away_player_placeholder' => $matchPay['away_player_placeholder'] ?? null,
                            'venue_id' => $vId,
                            'court_id' => $cId,
                            'start_date' => $matchPay['start_date'] ?? null,
                            'start_time' => $matchPay['start_time'] ?? null,
                            'status' => 'scheduled',
                        ]);

                        if (! empty($matchPay['scorer_ids']) && is_array($matchPay['scorer_ids'])) {
                            $match->scorers()->sync($matchPay['scorer_ids']);
                        }
                        if (! empty($matchPay['umpire_ids']) && is_array($matchPay['umpire_ids'])) {
                            $match->umpires()->sync($matchPay['umpire_ids']);
                        }
                    }
                }
            }
        });
    }

    public function getFixtures(User $club, string $tournamentId): array
    {
        $tournament = Tournament::find($tournamentId);
        if (! $tournament) {
            $this->apiError('Tournament not found.', 'NOT_FOUND', 404);
        }

        $format = strtolower($tournament->format ?? '');
        $groups = [];
        $fixtures = [];

        $tournamentType = $tournament->tournament_type ?? ($tournament->opponent_club_id ? 'CLUB_TO_CLUB' : 'CLUB_MEMBERS_ONLY');

        $formatFixture = function (TournamentFixture $f) use ($tournamentType) {
            $fMatches = [];
            foreach ($f->matches as $m) {
                $homePlayerMatch = null;
                if ($m->home_player_id && $m->homePlayer) {
                    $img = $m->homePlayer->profile_image;
                    $homePlayerMatch = [
                        'player_id' => (int) $m->home_player_id,
                        'full_name' => $m->homePlayer->name,
                        'profile_image' => $img ? (str_starts_with($img, 'http') ? $img : Storage::disk('public')->url($img)) : '',
                    ];
                } elseif ($m->home_player_id) {
                    $u = User::find($m->home_player_id);
                    $img = $u?->profile_image;
                    $homePlayerMatch = [
                        'player_id' => (int) $m->home_player_id,
                        'full_name' => $u?->name ?? "Player #{$m->home_player_id}",
                        'profile_image' => $img ? (str_starts_with($img, 'http') ? $img : Storage::disk('public')->url($img)) : '',
                    ];
                }

                $awayPlayerMatch = null;
                if ($m->away_player_id && $m->awayPlayer) {
                    $img = $m->awayPlayer->profile_image;
                    $awayPlayerMatch = [
                        'player_id' => (int) $m->away_player_id,
                        'full_name' => $m->awayPlayer->name,
                        'profile_image' => $img ? (str_starts_with($img, 'http') ? $img : Storage::disk('public')->url($img)) : '',
                    ];
                } elseif ($m->away_player_id) {
                    $u = User::find($m->away_player_id);
                    $img = $u?->profile_image;
                    $awayPlayerMatch = [
                        'player_id' => (int) $m->away_player_id,
                        'full_name' => $u?->name ?? "Player #{$m->away_player_id}",
                        'profile_image' => $img ? (str_starts_with($img, 'http') ? $img : Storage::disk('public')->url($img)) : '',
                    ];
                }

                $venueUser = $m->venue_id ? User::find($m->venue_id) : null;
                $venueName = $venueUser?->club_name ?? $venueUser?->name;

                $scorersList = $m->scorers->map(function ($s) {
                    $uName = $s->name ?? User::find($s->id)?->name;
                    return [
                        'id' => (int) $s->id,
                        'full_name' => $uName,
                    ];
                })->values()->all();

                $umpiresList = $m->umpires->map(function ($u) {
                    $uName = $u->name ?? User::find($u->id)?->name;
                    return [
                        'id' => (int) $u->id,
                        'full_name' => $uName,
                    ];
                })->values()->all();

                $fMatches[] = [
                    'match_id' => (int) $m->id,
                    'sequence' => (int) $m->sequence,
                    'home_player' => $homePlayerMatch,
                    'away_player' => $awayPlayerMatch,
                    'home_player_placeholder' => $m->home_player_placeholder,
                    'away_player_placeholder' => $m->away_player_placeholder,
                    'winner_player_id' => $m->winner_player_id ? (int) $m->winner_player_id : null,
                    'status' => $m->status,
                    'venue_id' => $m->venue_id ? (int) $m->venue_id : null,
                    'venue_name' => $venueName,
                    'court_id' => $m->court_id ? (int) $m->court_id : null,
                    'court_name' => $m->court?->name,
                    'start_date' => $m->start_date ? (is_string($m->start_date) ? $m->start_date : $m->start_date->format('Y-m-d')) : null,
                    'start_time' => $m->start_time,
                    'scorer_ids' => $m->scorers->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                    'scorers' => $scorersList,
                    'umpire_ids' => $m->umpires->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                    'umpires' => $umpiresList,
                    'scores' => [
                        'home_score' => (int) ($m->home_score ?? 0),
                        'away_score' => (int) ($m->away_score ?? 0),
                        'games' => $m->games ?? [],
                    ],
                ];
            }

            $fixData = [
                'fixture_id' => (int) $f->id,
                'round' => $f->round,
                'is_bye' => (bool) $f->is_bye,
                'is_rest' => (bool) $f->is_rest,
                'status' => $f->status,
            ];

            if ($tournamentType === 'CLUB_MEMBERS_ONLY') {
                $firstMatch = $f->matches->first();

                $homePId = $f->home_club_id ?: ($firstMatch?->home_player_id ?: null);
                $homePUser = $homePId ? User::find($homePId) : ($firstMatch?->homePlayer ?: null);

                $awayPId = $f->away_club_id ?: ($firstMatch?->away_player_id ?: null);
                $awayPUser = $awayPId ? User::find($awayPId) : ($firstMatch?->awayPlayer ?: null);

                if (! $homePUser && $f->is_bye) {
                    $byeId = $f->bye_club_id ?: ($homePId ?: ($firstMatch?->home_player_id ?: null));
                    if ($byeId) {
                        $homePUser = User::find($byeId);
                    }
                }

                $fixData['home_player'] = $homePUser ? [
                    'id' => (int) $homePUser->id,
                    'name' => $homePUser->name,
                    'email' => $homePUser->email,
                    'phone' => $homePUser->phone,
                    'profile_image' => $homePUser->profile_image ? (str_starts_with($homePUser->profile_image, 'http') ? $homePUser->profile_image : Storage::disk('public')->url($homePUser->profile_image)) : null,
                ] : null;

                $fixData['away_player'] = $awayPUser ? [
                    'id' => (int) $awayPUser->id,
                    'name' => $awayPUser->name,
                    'email' => $awayPUser->email,
                    'phone' => $awayPUser->phone,
                    'profile_image' => $awayPUser->profile_image ? (str_starts_with($awayPUser->profile_image, 'http') ? $awayPUser->profile_image : Storage::disk('public')->url($awayPUser->profile_image)) : null,
                ] : null;
            } else {
                $homeClub = null;
                if ($f->home_club_id && $f->homeClub) {
                    $homeClub = [
                        'club_id' => (int) $f->home_club_id,
                        'club_name' => $f->homeClub->club_name ?? $f->homeClub->name,
                        'club_logo' => app_image_url($f->homeClub->club_logo),
                    ];
                }

                $awayClub = null;
                if ($f->away_club_id && $f->awayClub) {
                    $awayClub = [
                        'club_id' => (int) $f->away_club_id,
                        'club_name' => $f->awayClub->club_name ?? $f->awayClub->name,
                        'club_logo' => app_image_url($f->awayClub->club_logo),
                    ];
                }

                $fixData['home_club'] = $homeClub;
                $fixData['away_club'] = $awayClub;
            }

            $fixData['home_placeholder'] = $f->home_placeholder;
            $fixData['away_placeholder'] = $f->away_placeholder;
            $fixData['matches'] = $fMatches;

            return $fixData;
        };

        if ($format === 'league') {
            $savedGroups = TournamentGroup::where('tournament_id', $tournament->id)
                ->with(['clubs:id,club_name,club_logo,name', 'fixtures.court:id,name,type', 'fixtures.homeClub:id,club_name,club_logo,name', 'fixtures.awayClub:id,club_name,club_logo,name', 'fixtures.byeClub:id,club_name,name', 'fixtures.restClub:id,club_name,name', 'fixtures.matches.court:id,name,type', 'fixtures.matches.homePlayer:id,name', 'fixtures.matches.awayPlayer:id,name', 'fixtures.matches.scorers:id,name', 'fixtures.matches.umpires:id,name'])
                ->get();

            foreach ($savedGroups as $g) {
                $gClubs = [];
                foreach ($g->clubs as $c) {
                    $gClubs[] = [
                        'club_id' => (int) $c->id,
                        'club_name' => $c->club_name ?? $c->name,
                        'club_logo' => app_image_url($c->club_logo),
                    ];
                }

                $gFixtures = [];
                foreach ($g->fixtures as $f) {
                    $gFixtures[] = $formatFixture($f);
                }

                $groups[] = [
                    'group_name' => $g->name,
                    'clubs' => $gClubs,
                    'fixtures' => $gFixtures,
                ];
            }
        } else {
            $savedFixtures = TournamentFixture::where('tournament_id', $tournament->id)
                ->whereNull('group_id')
                ->with(['court:id,name,type', 'homeClub:id,club_name,club_logo,name', 'awayClub:id,club_name,club_logo,name', 'byeClub:id,club_name,name', 'restClub:id,club_name,name', 'matches.court:id,name,type', 'matches.homePlayer:id,name', 'matches.awayPlayer:id,name', 'matches.scorers:id,name', 'matches.umpires:id,name'])
                ->get();

            foreach ($savedFixtures as $f) {
                $fixtures[] = $formatFixture($f);
            }
        }

        return [
            'tournament_id' => (int) $tournament->id,
            'tournament_type' => $tournamentType,
            'format' => $format,
            'group_count' => $format === 'league' ? count($groups) : null,
            'groups' => $groups,
            'fixtures' => $fixtures,
        ];
    }

    private function apiError(string $message, string $code, int $status = 422): never
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => $code,
        ], $status));
    }
}

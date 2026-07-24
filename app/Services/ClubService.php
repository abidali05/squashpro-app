<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Court;
use App\Models\Tournament;
use App\Models\User;
use App\Notifications\Booking\BookingStatusUpdatedNotification;
use App\Notifications\Court\CourtCreatedNotification;
use App\Notifications\Court\CourtMaintenanceNotification;
use App\Notifications\Tournament\TournamentCreatedNotification;
use App\Notifications\Tournament\TournamentInvitationNotification;
use App\Notifications\Tournament\TournamentAvailableNotification;
use App\Notifications\Tournament\TournamentInvitationAcceptedNotification;
use App\Notifications\Tournament\TournamentInvitationRejectedNotification;
use App\Notifications\Tournament\TournamentTeamSubmittedNotification;
use App\Models\ClubMembership;
use App\Models\TournamentRegistration;
use App\Support\AuditLogger;
use App\Support\ApiErrorCode;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

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
                $club->working_hours = $data['working_hours'];
            }

            if (array_key_exists('facilities', $data)) {
                $club->facilities = $this->normalizeFacilities($data['facilities']);
            }

            $club->number_of_courts = $club->courts()->count();
            $club->save();

            return $club->refresh();
        });
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

        $pendingMemberships = (int) \App\Models\ClubMembershipRequest::where('club_id', $club->id)
            ->where('status', 'pending')
            ->count();

        $pendingTournamentInvitations = (int) Tournament::where('opponent_club_id', $club->id)
            ->where('status', 'pending')
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

            $court = $court->refresh()->load('club');
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

            $court->save();

            return $court->refresh();
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
                  ->orWhere('opponent_club_id', $club->id);
            });

        $allTournaments = $baseQuery->get();

        $query = Tournament::query()
            ->where(function ($q) use ($club) {
                $q->where('club_id', $club->id)
                  ->orWhere('opponent_club_id', $club->id);
            })
            ->orderBy('id', 'desc');

        if ($status && in_array($status, ['pending', 'soft_accepted', 'confirmed', 'rejected', 'open', 'full', 'closed', 'completed', 'cancelled'], true)) {
            $query->where('status', $status);
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
        $now   = Carbon::now('Asia/Karachi');
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
                'pending_bookings'   => $pendingCount,
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

    public function storeTournament(User $club, array $data, ?UploadedFile $imageFile = null): Tournament
    {
        return DB::transaction(function () use ($club, $data, $imageFile) {
            $tournamentType = $data['tournament_type'] ?? 'CLUB_MEMBERS_ONLY';
            $status = $tournamentType === 'CLUB_TO_CLUB' ? 'pending' : 'open';
            $maximumPlayers = (int) ($data['maximum_players'] ?? $data['allowed_player'] ?? 0);

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
                'opponent_club_id' => $data['opponent_club_id'] ?? null,
                'gender' => $data['gender'] ?? 'OPEN',
                'player_level' => $data['player_level'] ?? null,
                'age_group' => $data['age_group'] ?? null,
            ]);

            $tournament = $tournament->refresh()->load('club');

            if ($tournamentType === 'CLUB_TO_CLUB') {
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
                        'opponent_club_id' => $tournament->opponent_club_id,
                    ]
                );

                // Notify opponent club
                $opponent = User::find($tournament->opponent_club_id);
                if ($opponent) {
                    $opponent->notify(new TournamentInvitationNotification($tournament));
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
            if (!$player || $player->status !== 'active') {
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
                if (!in_array($playerLevel, $allowedLevels, true)) {
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
            throw (new ModelNotFoundException())->setModel(Court::class, [$courtId]);
        }

        $court = $club->courts()->whereKey((int) $courtId)->first();

        if (! $court) {
            throw (new ModelNotFoundException())->setModel(Court::class, [$courtId]);
        }

        return $court;
    }

    private function findClubTournament(User $club, string $tournamentId): Tournament
    {
        if (! ctype_digit($tournamentId)) {
            throw (new ModelNotFoundException())->setModel(Tournament::class, [$tournamentId]);
        }

        $tournament = $club->tournaments()->whereKey((int) $tournamentId)->first();

        if (! $tournament) {
            throw (new ModelNotFoundException())->setModel(Tournament::class, [$tournamentId]);
        }

        return $tournament;
    }

    private function findClubBooking(User $club, string $bookingId): Booking
    {
        if (! ctype_digit($bookingId)) {
            throw (new ModelNotFoundException())->setModel(Booking::class, [$bookingId]);
        }

        $booking = $club->bookingsAsClub()
            ->with(['player', 'court'])
            ->whereKey((int) $bookingId)
            ->first();

        if (! $booking) {
            throw (new ModelNotFoundException())->setModel(Booking::class, [$bookingId]);
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
        $tournament = Tournament::find($tournamentId);
        if (!$tournament) {
            $this->apiError('Tournament not found.', 'NOT_FOUND', 404);
        }

        // Verify that the authenticated club is the invited club
        if ((int) $tournament->opponent_club_id !== $club->id) {
            $this->apiError('You are not authorized to respond to this invitation.', 'FORBIDDEN', 403);
        }

        // Verify that current status is pending
        if ($tournament->status !== 'pending') {
            $this->apiError('Invitation response already finalized.', 'ALREADY_RESPONDED', 409);
        }

        $newStatus = $decision === 'ACCEPT' ? 'soft_accepted' : 'rejected';

        DB::transaction(function () use ($tournament, $club, $newStatus, $decision) {
            $tournament->status = $newStatus;
            $tournament->save();

            // Log action in AuditLogger
            AuditLogger::log(
                actorId: $club->id,
                action: strtolower($decision) . '_tournament_invitation',
                entityType: Tournament::class,
                entityId: $tournament->id,
                before: ['status' => 'pending'],
                after: ['status' => $newStatus]
            );

            // Notify organizing club
            $organizer = $tournament->club;
            if ($organizer) {
                if ($decision === 'ACCEPT') {
                    $organizer->notify(new TournamentInvitationAcceptedNotification($tournament));
                } else {
                    $organizer->notify(new TournamentInvitationRejectedNotification($tournament));
                }
            }
        });

        return $newStatus;
    }

    public function eligiblePlayers(User $club, string $tournamentId, int $page = 1, int $limit = 20, ?string $search = null): array
    {
        $tournament = Tournament::find($tournamentId);
        if (!$tournament) {
            $this->apiError('Tournament not found.', 'NOT_FOUND', 404);
        }

        // Verify that the authenticated club is the invited club or organizing club
        if ((int) $tournament->opponent_club_id !== $club->id && (int) $tournament->club_id !== $club->id) {
            $this->apiError('You are not authorized to view eligible players for this tournament.', 'FORBIDDEN', 403);
        }

        // Query approved memberships of the invited club
        $invitedClubId = $tournament->opponent_club_id;

        // Extract age range from age_group
        $ageRange = explode('-', (string) $tournament->age_group);
        $minAge = isset($ageRange[0]) ? (int) $ageRange[0] : null;
        $maxAge = isset($ageRange[1]) ? (int) $ageRange[1] : null;

        $query = User::query()
            ->where('role', 'player')
            ->where('status', 'active')
            ->whereExists(function ($q) use ($invitedClubId) {
                $q->select(DB::raw(1))
                    ->from('club_memberships')
                    ->whereColumn('club_memberships.player_id', 'users.id')
                    ->where('club_memberships.club_id', $invitedClubId)
                    ->where('club_memberships.status', 'approved');
            });

        // 1. Gender check
        if ($tournament->gender !== 'OPEN' && $tournament->gender !== 'MIXED') {
            $query->whereRaw('LOWER(gender) = ?', [strtolower($tournament->gender)]);
        }

        // 2. Level check
        if ($tournament->player_level && is_array($tournament->player_level)) {
            $allowedLevels = array_map('strtolower', $tournament->player_level);
            $query->whereIn(DB::raw('LOWER(playing_level)'), $allowedLevels);
        }

        // 3. Age check
        if ($minAge !== null && $maxAge !== null) {
            $query->whereNotNull('dob');
            if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite') {
                $query->whereRaw("cast(strftime('%Y', 'now') - strftime('%Y', dob) as integer) BETWEEN ? AND ?", [$minAge, $maxAge]);
            } else {
                $query->whereRaw("TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN ? AND ?", [$minAge, $maxAge]);
            }
        }

        // Search check (by name or membership number)
        if ($search) {
            $query->where(function ($q) use ($search, $invitedClubId) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereExists(function ($sub) use ($search, $invitedClubId) {
                        $sub->select(DB::raw(1))
                            ->from('club_memberships')
                            ->whereColumn('club_memberships.player_id', 'users.id')
                            ->where('club_memberships.club_id', $invitedClubId)
                            ->where('club_memberships.membership_number', 'like', "%{$search}%");
                    });
            });
        }

        // Paginate
        $paginator = $query->paginate($limit, ['*'], 'page', $page);

        // Map results to the required format
        $data = collect($paginator->items())->map(function ($player) use ($invitedClubId) {
            $membership = ClubMembership::where('club_id', $invitedClubId)
                ->where('player_id', $player->id)
                ->first();

            return [
                'player_id' => $player->id,
                'full_name' => $player->name,
                'profile_image' => $player->profile_image ? asset('storage/' . $player->profile_image) : null,
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
        if (!$tournament) {
            $this->apiError('Tournament not found.', 'NOT_FOUND', 404);
        }

        // Verify that the authenticated club is the invited club
        if ((int) $tournament->opponent_club_id !== $club->id) {
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

        // Verify each player is eligible using the eligibility rules
        $eligibleList = $this->eligiblePlayers($club, $tournamentId, 1, 1000);
        $eligibleIds = collect($eligibleList['data'])->pluck('player_id')->toArray();

        foreach ($playerIds as $pid) {
            if (!in_array((int) $pid, $eligibleIds, true)) {
                $this->apiError("Player ID {$pid} is not eligible for this tournament.", 'PLAYER_NOT_ELIGIBLE', 422);
            }
        }

        DB::transaction(function () use ($tournament, $club, $playerIds) {
            // Save team roster to tournament_registrations
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
                before: ['status' => 'soft_accepted'],
                after: [
                    'status' => 'confirmed',
                    'player_ids' => $playerIds,
                ]
            );

            // Notify organizer club
            $organizer = $tournament->club;
            if ($organizer) {
                $organizer->notify(new TournamentTeamSubmittedNotification($tournament));
            }
        });

        return 'confirmed';
    }

    public function getTournamentTeam(User $club, string $tournamentId): array
    {
        $tournament = Tournament::find($tournamentId);
        if (!$tournament) {
            $this->apiError('Tournament not found.', 'NOT_FOUND', 404);
        }

        // Verify that the authenticated club is either the creator or the opponent
        if ((int) $tournament->club_id !== $club->id && (int) $tournament->opponent_club_id !== $club->id) {
            $this->apiError('You are not authorized to view the team roster for this tournament.', 'FORBIDDEN', 403);
        }

        $registrations = TournamentRegistration::where('tournament_id', $tournament->id)
            ->with(['player'])
            ->get();

        return $registrations->map(function ($reg) use ($tournament) {
            $player = $reg->player;
            if (!$player) {
                return null;
            }

            // Find membership relative to the opponent club if set, otherwise creator club
            $targetClubId = $tournament->opponent_club_id ?? $tournament->club_id;
            $membership = ClubMembership::where('club_id', $targetClubId)
                ->where('player_id', $player->id)
                ->first();

            return [
                'player_id' => $player->id,
                'full_name' => $player->name,
                'profile_image' => $player->profile_image ? asset('storage/' . $player->profile_image) : null,
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

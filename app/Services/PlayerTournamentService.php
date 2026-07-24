<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\User;
use App\Notifications\Tournament\TournamentRegisteredNotification;
use App\Notifications\Tournament\PlayerParticipationNotification;
use App\Models\ClubMembership;
use App\Support\AuditLogger;
use App\Support\ApiErrorCode;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PlayerTournamentService
{
    public function tournaments(User $player, ?string $filter = null, int $page = 1, int $limit = 40): array
    {
        $allTournaments = Tournament::query()
            ->with('club')
            ->withExists([
                'registrations as is_registered' => fn ($query) => $query
                    ->where('player_id', $player->id)
                    ->where('registration_status', 'registered'),
            ])
            ->orderByDesc('created_at')
            ->get();

        $filtered = $allTournaments->filter(function (Tournament $tournament) use ($player) {
            // 1. Membership Check: Player must be an approved member of the organizing club (or the opponent club for CLUB_TO_CLUB tournaments)
            $isMember = ClubMembership::where('player_id', $player->id)
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
                if (strcasecmp((string) $player->gender, (string) $tournament->gender) !== 0) {
                    return false;
                }
            }

            // Level match
            if ($tournament->player_level && is_array($tournament->player_level)) {
                $playerLevel = strtoupper((string) $player->playing_level);
                $allowedLevels = array_map('strtoupper', $tournament->player_level);
                if (!in_array($playerLevel, $allowedLevels, true)) {
                    return false;
                }
            }

            // Age match
            if ($tournament->age_group && $player->dob) {
                $ageRange = explode('-', (string) $tournament->age_group);
                $minAge = isset($ageRange[0]) ? (int) $ageRange[0] : null;
                $maxAge = isset($ageRange[1]) ? (int) $ageRange[1] : null;
                if ($minAge !== null && $maxAge !== null) {
                    $age = $player->dob->age;
                    if ($age < $minAge || $age > $maxAge) {
                        return false;
                    }
                }
            }

            return true;
        });

        // Apply filters (e.g. status)
        if ($filter) {
            $today = Carbon::today('Asia/Karachi')->toDateString();
            $filtered = $filtered->filter(function (Tournament $t) use ($filter, $today) {
                $deadline = $t->registration_deadline ? $t->registration_deadline->toDateString() : null;
                $startDate = $t->start_date ? $t->start_date->toDateString() : null;
                $endDate = $t->end_date ? $t->end_date->toDateString() : null;

                return match ($filter) {
                    'open' => $deadline >= $today,
                    'upcoming' => $startDate >= $today && $deadline < $today,
                    'ongoing' => $startDate <= $today && $endDate >= $today && !in_array($t->status, ['completed', 'cancelled']),
                    'completed' => $endDate < $today,
                    default => true,
                };
            });
        }

        // Paginate manually
        $total = $filtered->count();
        $offset = ($page - 1) * $limit;
        $items = $filtered->slice($offset, $limit)->values()->all();

        $paginator = new LengthAwarePaginator($items, $total, $limit, $page);

        return [
            'items' => $items,
            'pagination' => $this->pagination($paginator, $limit),
        ];
    }

    public function detail(User $player, int $tournamentId): Tournament
    {
        $tournament = Tournament::query()
            ->with('club')
            ->withExists([
                'registrations as is_registered' => fn ($query) => $query
                    ->where('player_id', $player->id)
                    ->where('registration_status', 'registered'),
            ])
            ->whereKey($tournamentId)
            ->first();

        if (! $tournament) {
            $this->apiError('Tournament does not exist.', ApiErrorCode::RECORD_NOT_FOUND, 404);
        }

        return $tournament;
    }

    public function paymentMethods(): array
    {
        return [
            // ['id' => 'card', 'name' => 'Debit/Credit Card'],
            // ['id' => 'wallet', 'name' => 'App Wallet'],
            ['id' => 'cash', 'name' => 'Cash'],
            // ['id' => 'jazzcash', 'name' => 'JazzCash'],
            // ['id' => 'easypaisa', 'name' => 'Easypaisa'],
        ];
    }

    public function register(User $player, int $tournamentId, string $paymentMethodId): TournamentRegistration
    {
        return DB::transaction(function () use ($player, $tournamentId, $paymentMethodId) {
            $tournament = Tournament::query()
                ->whereKey($tournamentId)
                ->lockForUpdate()
                ->first();

            if (! $tournament) {
                $this->apiError('Tournament does not exist.', ApiErrorCode::RECORD_NOT_FOUND, 404);
            }

            if (! $this->canAcceptRegistration($tournament)) {
                $this->apiError('Only upcoming open tournaments can accept registrations.', ApiErrorCode::VALIDATION_ERROR);
            }

            $alreadyRegistered = TournamentRegistration::query()
                ->where('tournament_id', $tournament->id)
                ->where('player_id', $player->id)
                ->where('registration_status', 'registered')
                ->exists();

            if ($alreadyRegistered) {
                $this->apiError('Player is already registered in this tournament.', ApiErrorCode::DUPLICATE_RESOURCE);
            }

            if ((int) $tournament->registered_players_count >= (int) $tournament->allowed_player) {
                $tournament->status = 'full';
                $tournament->save();

                $this->apiError('Tournament is full.', ApiErrorCode::VALIDATION_ERROR);
            }

            $registration = TournamentRegistration::create([
                'tournament_id' => $tournament->id,
                'player_id' => $player->id,
                'payment_method_id' => $paymentMethodId,
                'payment_status' => 'paid',
                'registration_status' => 'registered',
                'amount' => $tournament->entry_fees,
                'currency' => 'PKR',
            ]);

            $tournament->registered_players_count = ((int) $tournament->registered_players_count) + 1;
            if ((int) $tournament->registered_players_count >= (int) $tournament->allowed_player) {
                $tournament->status = 'full';
            }
            $tournament->save();

            $registration = $registration->load(['tournament.club', 'player']);
            $registration->tournament->club?->notify((new TournamentRegisteredNotification($registration))->afterCommit());

            return $registration;
        });
    }

    private function canAcceptRegistration(Tournament $tournament): bool
    {
        return $tournament->status === 'open'
            && $tournament->start_date?->toDateString() > Carbon::today('Asia/Karachi')->toDateString();
    }

    public function respondToParticipation(User $player, int $tournamentId, string $decision): string
    {
        return DB::transaction(function () use ($player, $tournamentId, $decision) {
            $tournament = Tournament::query()
                ->whereKey($tournamentId)
                ->lockForUpdate()
                ->first();

            if (!$tournament) {
                $this->apiError('Tournament does not exist.', ApiErrorCode::RECORD_NOT_FOUND, 404);
            }

            // Verify tournament type
            if ($tournament->tournament_type !== 'CLUB_MEMBERS_ONLY') {
                $this->apiError('This endpoint is only for internal club members tournaments.', ApiErrorCode::VALIDATION_ERROR, 403);
            }

            // Verify organizing club approved membership
            $membership = ClubMembership::where('player_id', $player->id)
                ->where('club_id', $tournament->club_id)
                ->where('status', 'approved')
                ->first();

            if (!$membership) {
                $this->apiError('Player is not an approved member of the organizing club.', 'PLAYER_NOT_ELIGIBLE', 422);
            }

            // Revalidate player eligibility
            // 1. Gender check
            if ($tournament->gender !== 'OPEN' && $tournament->gender !== 'MIXED') {
                if (strcasecmp((string) $player->gender, (string) $tournament->gender) !== 0) {
                    $this->apiError('Player gender does not match tournament criteria.', 'PLAYER_NOT_ELIGIBLE', 422);
                }
            }

            // 2. Level check
            if ($tournament->player_level && is_array($tournament->player_level)) {
                $playerLevel = strtoupper((string) $player->playing_level);
                $allowedLevels = array_map('strtoupper', $tournament->player_level);
                if (!in_array($playerLevel, $allowedLevels, true)) {
                    $this->apiError('Player level does not match tournament criteria.', 'PLAYER_NOT_ELIGIBLE', 422);
                }
            }

            // 3. Age check
            if ($tournament->age_group && $player->dob) {
                $ageRange = explode('-', (string) $tournament->age_group);
                $minAge = isset($ageRange[0]) ? (int) $ageRange[0] : null;
                $maxAge = isset($ageRange[1]) ? (int) $ageRange[1] : null;
                if ($minAge !== null && $maxAge !== null) {
                    $age = $player->dob->age;
                    if ($age < $minAge || $age > $maxAge) {
                        $this->apiError('Player age does not match tournament criteria.', 'PLAYER_NOT_ELIGIBLE', 422);
                    }
                }
            }

            // Verify deadline
            if ($tournament->registration_deadline && now()->greaterThan($tournament->registration_deadline)) {
                $this->apiError('Registration deadline has passed.', 'REGISTRATION_CLOSED', 410);
            }

            if ($decision === 'ACCEPT') {
                // Verify already registered
                $alreadyRegistered = TournamentRegistration::where('tournament_id', $tournament->id)
                    ->where('player_id', $player->id)
                    ->where('registration_status', 'registered')
                    ->exists();

                if ($alreadyRegistered) {
                    $this->apiError('Player is already registered.', 'ALREADY_RESPONDED', 409);
                }

                // Verify capacity
                if ((int) $tournament->registered_players_count >= (int) $tournament->allowed_player) {
                    $this->apiError('Tournament capacity reached.', 'CAPACITY_REACHED', 409);
                }

                // Register
                TournamentRegistration::updateOrCreate(
                    [
                        'tournament_id' => $tournament->id,
                        'player_id' => $player->id,
                    ],
                    [
                        'payment_method_id' => 'members_only',
                        'payment_status' => 'paid',
                        'registration_status' => 'registered',
                        'amount' => $tournament->entry_fees ?? 0,
                        'currency' => 'PKR',
                    ]
                );

                // Increment count
                $tournament->registered_players_count = ((int) $tournament->registered_players_count) + 1;
                if ((int) $tournament->registered_players_count >= (int) $tournament->allowed_player) {
                    $tournament->status = 'full';
                }
                $tournament->save();

                // Log audit history
                AuditLogger::log(
                    actorId: $player->id,
                    action: 'accept_tournament_participation',
                    entityType: Tournament::class,
                    entityId: $tournament->id,
                    before: null,
                    after: ['status' => 'registered']
                );

                // Notify player
                $player->notify(new PlayerParticipationNotification($tournament, 'ACCEPT'));

                return 'registered';
            } else {
                // REJECT decision
                $registration = TournamentRegistration::where('tournament_id', $tournament->id)
                    ->where('player_id', $player->id)
                    ->first();

                if ($registration && $registration->registration_status === 'registered') {
                    $registration->registration_status = 'cancelled';
                    $registration->save();

                    // Decrement count
                    $tournament->registered_players_count = max(0, ((int) $tournament->registered_players_count) - 1);
                    if ($tournament->status === 'full' && (int) $tournament->registered_players_count < (int) $tournament->allowed_player) {
                        $tournament->status = 'open';
                    }
                    $tournament->save();
                }

                // Log audit
                AuditLogger::log(
                    actorId: $player->id,
                    action: 'reject_tournament_participation',
                    entityType: Tournament::class,
                    entityId: $tournament->id,
                    before: null,
                    after: ['status' => 'cancelled']
                );

                // Notify player
                $player->notify(new PlayerParticipationNotification($tournament, 'REJECT'));

                return 'cancelled';
            }
        });
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

<?php

namespace App\Services;

use App\Models\ClubTournamentRule;
use App\Models\TournamentMatch;
use App\Models\TournamentMatchGame;
use App\Models\TournamentMatchRally;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class SquashMatchScoringService
{
    /**
     * Helper to resolve scoring rules (best_of, points_per_game, win_by) from tournament rules or match defaults.
     */
    public function getScoringRules(TournamentMatch $match): array
    {
        $bestOf = $match->best_of ?? 3;
        $pointsPerGame = 11;
        $winBy = 2;

        $tournamentId = $match->fixture?->tournament_id;
        if ($tournamentId) {
            $rule = ClubTournamentRule::where('tournament_id', $tournamentId)->first();
            if ($rule && !empty($rule->scoring_rules)) {
                if (!empty($rule->scoring_rules['best_of'])) {
                    $bestOf = (int) $rule->scoring_rules['best_of'];
                }
                if (!empty($rule->scoring_rules['points_per_game'])) {
                    $pointsPerGame = (int) $rule->scoring_rules['points_per_game'];
                }
                if (!empty($rule->scoring_rules['win_by'])) {
                    $winBy = (int) $rule->scoring_rules['win_by'];
                }
            }
        }

        return [
            'best_of' => $bestOf,
            'points_per_game' => $pointsPerGame,
            'win_by' => $winBy,
        ];
    }

    /**
     * Start a squash match with toss and initial server election.
     */
    public function startMatch(TournamentMatch $match, int $tossWinnerPlayerId, int $initialServerPlayerId, string $initialServingSide): array
    {
        return DB::transaction(function () use ($match, $tossWinnerPlayerId, $initialServerPlayerId, $initialServingSide) {
            $now = Carbon::now();
            $rules = $this->getScoringRules($match);

            $match->update([
                'status' => 'live',
                'best_of' => $rules['best_of'],
                'toss_winner_player_id' => $tossWinnerPlayerId,
                'initial_server_player_id' => $initialServerPlayerId,
                'initial_serving_side' => strtoupper($initialServingSide),
                'current_server_id' => $initialServerPlayerId,
                'current_serving_side' => strtoupper($initialServingSide),
                'can_change_serving_side' => true,
                'match_start_time' => $now,
                'current_game' => 1,
                'current_game_start_time' => $now,
            ]);

            // Create or reset Game 1
            TournamentMatchGame::where('match_id', $match->id)->delete();
            TournamentMatchRally::where('match_id', $match->id)->delete();

            TournamentMatchGame::create([
                'match_id' => $match->id,
                'game_number' => 1,
                'home_score' => 0,
                'away_score' => 0,
                'starting_server_id' => $initialServerPlayerId,
                'starting_serving_side' => strtoupper($initialServingSide),
                'start_time' => $now,
                'status' => 'in_progress',
            ]);

            return $this->getLiveMatchStatePayload($match->fresh());
        });
    }

    /**
     * Record a rally outcome and process PARS-11 state transitions.
     */
    public function recordRally(
        TournamentMatch $match,
        string $callType,
        ?int $awardedToPlayerId = null,
        ?int $strikerPlayerId = null,
        ?string $handoutChosenSide = null
    ): array {
        return DB::transaction(function () use ($match, $callType, $awardedToPlayerId, $strikerPlayerId, $handoutChosenSide) {
            $currentGame = TournamentMatchGame::where('match_id', $match->id)
                ->where('status', 'in_progress')
                ->first();

            if (!$currentGame) {
                // If game was completed or not started, find or create active game
                $currentGame = TournamentMatchGame::where('match_id', $match->id)
                    ->where('game_number', $match->current_game)
                    ->first();

                if (!$currentGame) {
                    $now = Carbon::now();
                    $currentGame = TournamentMatchGame::create([
                        'match_id' => $match->id,
                        'game_number' => $match->current_game,
                        'home_score' => 0,
                        'away_score' => 0,
                        'starting_server_id' => $match->current_server_id,
                        'starting_serving_side' => $match->current_serving_side,
                        'start_time' => $now,
                        'status' => 'in_progress',
                    ]);
                }
            }

            $rules = $this->getScoringRules($match);
            $pointsPerGame = $rules['points_per_game'];
            $winBy = $rules['win_by'];
            $bestOf = $rules['best_of'];

            $currentServerId = $match->current_server_id ?? $match->home_player_id;
            $currentServingSide = strtoupper($match->current_serving_side ?? 'R');
            $callType = strtolower($callType);

            $nextServerId = $currentServerId;
            $nextServingSide = $currentServingSide;
            $canChangeServingSide = false;

            if ($callType === 'let') {
                // LET: 0 points awarded, exact same server & box, side locked
                $nextServerId = $currentServerId;
                $nextServingSide = $currentServingSide;
                $canChangeServingSide = false;
                $awardedToPlayerId = null; // Nullable for LET
            } else {
                if (!$awardedToPlayerId) {
                    throw new Exception("awarded_to_player_id is required for {$callType} call.");
                }

                // Award point
                if ($awardedToPlayerId == $match->home_player_id) {
                    $currentGame->home_score += 1;
                } else {
                    $currentGame->away_score += 1;
                }

                if ($awardedToPlayerId == $currentServerId) {
                    // Server Wins Rally (Continuous Serve)
                    $nextServerId = $currentServerId;
                    $nextServingSide = ($currentServingSide === 'R') ? 'L' : 'R';
                    $canChangeServingSide = false;
                } else {
                    // Receiver Wins Rally (Hand-Out)
                    $nextServerId = $awardedToPlayerId;
                    $nextServingSide = !empty($handoutChosenSide) ? strtoupper($handoutChosenSide) : 'L';
                    $canChangeServingSide = true;
                }
            }

            $currentGame->save();

            // Sequence inside current game
            $lastSequence = TournamentMatchRally::where('game_id', $currentGame->id)->max('sequence') ?? 0;
            $sequence = $lastSequence + 1;
            $now = Carbon::now();

            $rally = TournamentMatchRally::create([
                'match_id' => $match->id,
                'game_id' => $currentGame->id,
                'sequence' => $sequence,
                'server_player_id' => $currentServerId,
                'serving_side' => $currentServingSide,
                'call_type' => $callType,
                'striker_player_id' => $strikerPlayerId ?? $awardedToPlayerId ?? $currentServerId,
                'awarded_to_player_id' => $awardedToPlayerId,
                'home_score_after' => $currentGame->home_score,
                'away_score_after' => $currentGame->away_score,
                'next_server_player_id' => $nextServerId,
                'next_serving_side' => $nextServingSide,
                'can_change_serving_side' => $canChangeServingSide,
                'event_time' => $now,
            ]);

            // Update match live state
            $match->update([
                'current_server_id' => $nextServerId,
                'current_serving_side' => $nextServingSide,
                'can_change_serving_side' => $canChangeServingSide,
            ]);

            // Check Game Winning Rule (target points, win_by lead)
            $isGameOver = false;
            $isMatchOver = false;

            $homeScore = $currentGame->home_score;
            $awayScore = $currentGame->away_score;

            if (($homeScore >= $pointsPerGame || $awayScore >= $pointsPerGame) && abs($homeScore - $awayScore) >= $winBy) {
                $isGameOver = true;
                $gameWinnerId = ($homeScore > $awayScore) ? $match->home_player_id : $match->away_player_id;

                // Ensure non-negative integer for duration_seconds
                $startTime = $currentGame->start_time ? Carbon::parse($currentGame->start_time) : null;
                $duration = 0;
                if ($startTime) {
                    $duration = (int) max(0, (int) round(abs($now->diffInSeconds($startTime))));
                }

                $currentGame->update([
                    'status' => 'completed',
                    'winner_player_id' => $gameWinnerId,
                    'end_time' => $now,
                    'duration_seconds' => $duration,
                ]);

                // Check Match Winning Rule
                $gamesNeededToWin = (int) ceil($bestOf / 2);

                $completedGames = TournamentMatchGame::where('match_id', $match->id)
                    ->where('status', 'completed')
                    ->get();

                $homeGamesWon = $completedGames->where('winner_player_id', $match->home_player_id)->count();
                $awayGamesWon = $completedGames->where('winner_player_id', $match->away_player_id)->count();

                if ($homeGamesWon >= $gamesNeededToWin || $awayGamesWon >= $gamesNeededToWin) {
                    $isMatchOver = true;
                    $matchWinnerId = ($homeGamesWon > $awayGamesWon) ? $match->home_player_id : $match->away_player_id;

                    $match->update([
                        'winner_player_id' => $matchWinnerId,
                        'match_end_time' => $now,
                    ]);
                } else {
                    // Match continues -> advance current_game
                    $nextGameNumber = $match->current_game + 1;
                    $match->update([
                        'current_game' => $nextGameNumber,
                        'current_server_id' => $gameWinnerId,
                        'current_serving_side' => 'L',
                        'can_change_serving_side' => true,
                        'current_game_start_time' => $now,
                    ]);

                    // Create next game record
                    TournamentMatchGame::create([
                        'match_id' => $match->id,
                        'game_number' => $nextGameNumber,
                        'home_score' => 0,
                        'away_score' => 0,
                        'starting_server_id' => $gameWinnerId,
                        'starting_serving_side' => 'L',
                        'start_time' => $now,
                        'status' => 'in_progress',
                    ]);
                }
            }

            $payload = $this->getLiveMatchStatePayload($match->fresh());

            // Build latest_event payload
            $serverUser = User::find($currentServerId);
            $nextServerUser = User::find($nextServerId);
            $awardedUser = $awardedToPlayerId ? User::find($awardedToPlayerId) : null;
            $awardedName = $awardedUser?->name ?? ($awardedToPlayerId ? ($awardedToPlayerId == $match->home_player_id ? $match->home_player_placeholder : $match->away_player_placeholder) : null);

            $payload['latest_event'] = [
                'sequence' => $rally->sequence,
                'game_number' => $currentGame->game_number,
                'server_id' => $currentServerId,
                'server_name' => $serverUser?->name ?? ($currentServerId == $match->home_player_id ? $match->home_player_placeholder : $match->away_player_placeholder),
                'serving_side' => $currentServingSide,
                'call_type' => $callType,
                'score_after' => "{$currentGame->home_score}-{$currentGame->away_score}",
                'next_server_id' => $nextServerId,
                'next_server_name' => $nextServerUser?->name ?? ($nextServerId == $match->home_player_id ? $match->home_player_placeholder : $match->away_player_placeholder),
                'next_serving_side' => $nextServingSide,
                'awarded_to_player_id' => $awardedToPlayerId,
                'awarded_to_player_name' => $awardedName,
                'timestamp' => $rally->event_time?->toIso8601String() ?? $now->toIso8601String(),
            ];

            return $payload;
        });
    }

    /**
     * Undo the most recent non-undone rally.
     */
    public function undoLastRally(TournamentMatch $match, ?int $undoneById = null): array
    {
        return DB::transaction(function () use ($match, $undoneById) {
            $lastRally = TournamentMatchRally::where('match_id', $match->id)
                ->where('is_undone', false)
                ->orderBy('id', 'desc')
                ->first();

            if (!$lastRally) {
                throw new Exception("No active rally to undo.");
            }

            $lastRally->update([
                'is_undone' => true,
                'undone_at' => Carbon::now(),
                'undone_by' => $undoneById,
            ]);

            // Re-evaluate game and match state
            $currentGameNumber = $match->current_game;
            $currentGame = TournamentMatchGame::where('match_id', $match->id)
                ->where('game_number', $currentGameNumber)
                ->first();

            // Check remaining rallies in current game
            $remainingRalliesInCurrentGame = TournamentMatchRally::where('game_id', $currentGame?->id)
                ->where('is_undone', false)
                ->get();

            if ($remainingRalliesInCurrentGame->isEmpty() && $currentGameNumber > 1) {
                // If game was newly created after game win, delete current empty game and revert to previous game
                $currentGame?->delete();

                $currentGameNumber = $currentGameNumber - 1;
                $match->update(['current_game' => $currentGameNumber]);

                $currentGame = TournamentMatchGame::where('match_id', $match->id)
                    ->where('game_number', $currentGameNumber)
                    ->first();
            }

            if ($currentGame) {
                $currentGameRallies = TournamentMatchRally::where('game_id', $currentGame->id)
                    ->where('is_undone', false)
                    ->orderBy('sequence', 'asc')
                    ->get();

                $lastRallyInGame = $currentGameRallies->last();

                $homeScore = $lastRallyInGame ? $lastRallyInGame->home_score_after : 0;
                $awayScore = $lastRallyInGame ? $lastRallyInGame->away_score_after : 0;

                $currentGame->update([
                    'home_score' => $homeScore,
                    'away_score' => $awayScore,
                    'status' => 'in_progress',
                    'winner_player_id' => null,
                    'end_time' => null,
                    'duration_seconds' => null,
                ]);

                if ($lastRallyInGame) {
                    $match->update([
                        'status' => 'live',
                        'winner_player_id' => null,
                        'match_end_time' => null,
                        'current_server_id' => $lastRallyInGame->next_server_player_id,
                        'current_serving_side' => $lastRallyInGame->next_serving_side,
                        'can_change_serving_side' => $lastRallyInGame->can_change_serving_side,
                    ]);
                } else {
                    // Reset to game start state
                    $match->update([
                        'status' => 'live',
                        'winner_player_id' => null,
                        'match_end_time' => null,
                        'current_server_id' => $currentGame->starting_server_id ?? $match->initial_server_player_id,
                        'current_serving_side' => $currentGame->starting_serving_side ?? $match->initial_serving_side,
                        'can_change_serving_side' => true,
                    ]);
                }
            }

            return $this->getLiveMatchStatePayload($match->fresh());
        });
    }

    /**
     * Finalize & complete match results with strict rules checks.
     */
    public function completeMatch(TournamentMatch $match, ?int $winnerPlayerId = null): array
    {
        return DB::transaction(function () use ($match, $winnerPlayerId) {
            $rules = $this->getScoringRules($match);
            $bestOf = $rules['best_of'];
            $gamesNeededToWin = (int) ceil($bestOf / 2);

            $completedGames = TournamentMatchGame::where('match_id', $match->id)
                ->where('status', 'completed')
                ->orderBy('game_number', 'asc')
                ->get();

            $homeGamesWon = $completedGames->where('winner_player_id', $match->home_player_id)->count();
            $awayGamesWon = $completedGames->where('winner_player_id', $match->away_player_id)->count();

            // CHECK: Match cannot be completed if neither player has won the required games (Best of 3 -> 2 games, Best of 5 -> 3 games)
            if ($homeGamesWon < $gamesNeededToWin && $awayGamesWon < $gamesNeededToWin) {
                throw new Exception(
                    "Cannot complete match yet. Based on tournament rules (Best of {$bestOf}), a player must win at least {$gamesNeededToWin} games to complete the match. Current games won: {$homeGamesWon}-{$awayGamesWon}."
                );
            }

            if (!$winnerPlayerId) {
                $winnerPlayerId = ($homeGamesWon >= $awayGamesWon) ? $match->home_player_id : $match->away_player_id;
            } else {
                // Verify winner is valid
                $actualWinnerId = ($homeGamesWon >= $awayGamesWon) ? $match->home_player_id : $match->away_player_id;
                if ($winnerPlayerId != $actualWinnerId) {
                    throw new Exception("Invalid winner_player_id provided. Based on completed games ({$homeGamesWon}-{$awayGamesWon}), the winner must be player ID {$actualWinnerId}.");
                }
            }

            $now = Carbon::now();

            // Build score string e.g. "2-0 (11-7, 11-9)"
            $gameScoresStr = $completedGames->map(function ($g) {
                return "{$g->home_score}-{$g->away_score}";
            })->implode(', ');

            $finalScoreStr = "{$homeGamesWon}-{$awayGamesWon}" . ($gameScoresStr ? " ({$gameScoresStr})" : "");

            $match->update([
                'status' => 'completed',
                'winner_player_id' => $winnerPlayerId,
                'match_end_time' => $now,
                'score' => $finalScoreStr,
            ]);

            $winnerUser = User::find($winnerPlayerId);
            $winnerName = $winnerUser?->name ?? ($winnerPlayerId == $match->home_player_id ? $match->home_player_placeholder : $match->away_player_placeholder);

            return [
                'match_id' => $match->id,
                'status' => 'completed',
                'winner_player_id' => $winnerPlayerId,
                'winner_name' => $winnerName,
                'final_score' => $finalScoreStr,
            ];
        });
    }

    /**
     * Construct live match state payload exact to backend specification.
     */
    public function getLiveMatchStatePayload(TournamentMatch $match): array
    {
        $match->loadMissing(['homePlayer', 'awayPlayer', 'winnerPlayer', 'court', 'venue', 'fixture.tournament']);

        $rules = $this->getScoringRules($match);
        $bestOf = $rules['best_of'];
        $pointsPerGame = $rules['points_per_game'];
        $winBy = $rules['win_by'];

        $games = TournamentMatchGame::where('match_id', $match->id)
            ->orderBy('game_number', 'asc')
            ->get();

        $completedGames = $games->where('status', 'completed');

        $p1GamesWon = $completedGames->where('winner_player_id', $match->home_player_id)->count();
        $p2GamesWon = $completedGames->where('winner_player_id', $match->away_player_id)->count();

        $currentGame = $games->where('game_number', $match->current_game)->first();
        if (!$currentGame) {
            $currentGame = $games->first();
        }

        $p1CurrentScore = $currentGame ? $currentGame->home_score : 0;
        $p2CurrentScore = $currentGame ? $currentGame->away_score : 0;

        $p1GameScores = $completedGames->map(fn($g) => (int) $g->home_score)->values()->toArray();
        $p2GameScores = $completedGames->map(fn($g) => (int) $g->away_score)->values()->toArray();

        $gamesNeededToWin = (int) ceil($bestOf / 2);

        $isMatchOver = ($match->status === 'completed') || ($p1GamesWon >= $gamesNeededToWin || $p2GamesWon >= $gamesNeededToWin);
        $isGameOver = $currentGame ? ($currentGame->status === 'completed') : false;

        // Game timings array
        $gameTimings = $completedGames->map(function ($g) use ($match) {
            $winner = User::find($g->winner_player_id);
            $winnerName = $winner?->name ?? ($g->winner_player_id == $match->home_player_id ? $match->home_player_placeholder : $match->away_player_placeholder);

            return [
                'game_number' => (int) $g->game_number,
                'start_time' => $g->start_time?->toIso8601String(),
                'end_time' => $g->end_time?->toIso8601String(),
                'duration_seconds' => $g->duration_seconds ? (int) $g->duration_seconds : 0,
                'score' => "{$g->home_score}-{$g->away_score}",
                'winner_player_id' => $g->winner_player_id,
                'winner_name' => $winnerName,
                'p1_score' => (int) $g->home_score,
                'p2_score' => (int) $g->away_score,
            ];
        })->values()->toArray();

        // History array
        $rallies = TournamentMatchRally::where('match_id', $match->id)
            ->where('is_undone', false)
            ->with(['server', 'nextServer', 'awardedTo', 'game'])
            ->orderBy('id', 'asc')
            ->get();

        $history = $rallies->map(function ($r) use ($match) {
            $serverName = $r->server?->name ?? ($r->server_player_id == $match->home_player_id ? $match->home_player_placeholder : $match->away_player_placeholder);
            $nextServerName = $r->nextServer?->name ?? ($r->next_server_player_id == $match->home_player_id ? $match->home_player_placeholder : $match->away_player_placeholder);
            $awardedName = $r->awardedTo?->name ?? ($r->awarded_to_player_id ? ($r->awarded_to_player_id == $match->home_player_id ? $match->home_player_placeholder : $match->away_player_placeholder) : null);

            return [
                'sequence' => (int) $r->sequence,
                'game_number' => (int) ($r->game?->game_number ?? 1),
                'server_id' => $r->server_player_id,
                'server_name' => $serverName,
                'serving_side' => $r->serving_side,
                'call_type' => $r->call_type,
                'awarded_to_player_id' => $r->awarded_to_player_id,
                'awarded_to_player_name' => $awardedName,
                'score_after' => "{$r->home_score_after}-{$r->away_score_after}",
                'next_server_id' => $r->next_server_player_id,
                'next_server_name' => $nextServerName,
                'next_serving_side' => $r->next_serving_side,
                'timestamp' => $r->event_time?->toIso8601String(),
            ];
        })->values()->toArray();

        $winnerName = $match->winnerPlayer?->name ?? ($match->winner_player_id ? ($match->winner_player_id == $match->home_player_id ? $match->home_player_placeholder : $match->away_player_placeholder) : null);

        $tournamentName = $match->fixture?->tournament?->name ?? 'Squash Tournament';
        $courtName = $match->court?->name ?? 'Court 1';
        $venueName = $match->venue?->name ?? $match->venue?->club_name ?? $match->court?->name ?? 'Squash Venue';

        return [
            'match_id' => $match->id,
            'tournament_name' => $tournamentName,
            'venue_name' => $venueName,
            'court_name' => $courtName,
            'round_name' => $match->fixture?->round ?? 'Match Round',
            'status' => $match->status,
            'best_of' => $bestOf,
            'points_per_game' => $pointsPerGame,
            'win_by' => $winBy,
            'current_game' => (int) ($match->current_game ?? 1),
            'match_start_time' => $match->match_start_time?->toIso8601String(),
            'match_end_time' => $match->match_end_time?->toIso8601String(),
            'current_game_start_time' => $match->current_game_start_time?->toIso8601String() ?? $match->match_start_time?->toIso8601String(),
            'player_one' => [
                'player_id' => $match->home_player_id,
                'name' => $match->homePlayer?->name ?? $match->home_player_placeholder ?? 'Player 1',
                'games_won' => $p1GamesWon,
                'current_game_score' => $p1CurrentScore,
                'game_scores' => $p1GameScores,
            ],
            'player_two' => [
                'player_id' => $match->away_player_id,
                'name' => $match->awayPlayer?->name ?? $match->away_player_placeholder ?? 'Player 2',
                'games_won' => $p2GamesWon,
                'current_game_score' => $p2CurrentScore,
                'game_scores' => $p2GameScores,
            ],
            'current_server_id' => $match->current_server_id,
            'current_serving_side' => $match->current_serving_side,
            'can_change_serving_side' => (bool) $match->can_change_serving_side,
            'serving_state' => 'ready_to_serve',
            'is_game_over' => $isGameOver,
            'is_match_over' => $isMatchOver,
            'winner_player_id' => $match->winner_player_id,
            'winner_name' => $winnerName,
            'game_timings' => $gameTimings,
            'history' => $history,
        ];
    }
}

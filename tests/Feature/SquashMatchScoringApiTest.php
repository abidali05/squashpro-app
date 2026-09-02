<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\TournamentFixture;
use App\Models\TournamentMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SquashMatchScoringApiTest extends TestCase
{
    use RefreshDatabase;

    private User $player1;
    private User $player2;
    private TournamentMatch $match;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test players
        $this->player1 = User::factory()->create([
            'role' => 'player',
            'name' => 'Usama',
        ]);

        $this->player2 = User::factory()->create([
            'role' => 'player',
            'name' => 'Muneeb',
        ]);

        $club = User::factory()->create([
            'role' => 'club',
            'name' => 'Lahore Club',
        ]);

        // Create tournament & fixture
        $tournament = Tournament::create([
            'club_id' => $club->id,
            'name' => 'Lahore Open Squash Championship',
            'tournament_type' => 'singles',
            'format' => 'knockout',
            'status' => 'ongoing',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'registration_deadline' => now()->subDay()->toDateString(),
        ]);

        $fixture = TournamentFixture::create([
            'tournament_id' => $tournament->id,
            'round' => 'Round 1',
            'home_club_id' => $club->id,
            'status' => 'scheduled',
        ]);

        // Create match
        $this->match = TournamentMatch::create([
            'fixture_id' => $fixture->id,
            'sequence' => 1,
            'home_player_id' => $this->player1->id,
            'away_player_id' => $this->player2->id,
            'status' => 'scheduled',
            'best_of' => 3,
        ]);

        $rawToken = 'test_token_' . $this->player1->id;
        $this->player1->api_access_token = hash('sha256', $rawToken);
        $this->player1->save();
        $this->token = $rawToken;
    }

    public function test_complete_squash_match_scoring_workflow(): void
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ];

        // 1. Start Match
        $startResponse = $this->withHeaders($headers)->postJson("/api/v1/player/matches/{$this->match->id}/start", [
            'toss_winner_player_id' => $this->player1->id,
            'initial_server_player_id' => $this->player1->id,
            'initial_serving_side' => 'R',
        ]);

        $startResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.match_id', $this->match->id)
            ->assertJsonPath('data.current_server_id', $this->player1->id)
            ->assertJsonPath('data.current_serving_side', 'R')
            ->assertJsonPath('data.can_change_serving_side', true);

        // 2. Record Rally - Server Wins (Usama continuous serve, box alternates R -> L)
        $rally1Response = $this->withHeaders($headers)->postJson("/api/v1/player/matches/{$this->match->id}/rally", [
            'call_type' => 'clean_winner',
            'awarded_to_player_id' => $this->player1->id,
            'striker_player_id' => $this->player1->id,
        ]);

        $rally1Response->assertStatus(200)
            ->assertJsonPath('data.player_one.current_game_score', 1)
            ->assertJsonPath('data.current_server_id', $this->player1->id)
            ->assertJsonPath('data.current_serving_side', 'L')
            ->assertJsonPath('data.can_change_serving_side', false);

        // 3. Record Rally - LET call (0 points, exact same server & side)
        $letResponse = $this->withHeaders($headers)->postJson("/api/v1/player/matches/{$this->match->id}/rally", [
            'call_type' => 'let',
            'awarded_to_player_id' => $this->player1->id,
        ]);

        $letResponse->assertStatus(200)
            ->assertJsonPath('data.player_one.current_game_score', 1)
            ->assertJsonPath('data.current_server_id', $this->player1->id)
            ->assertJsonPath('data.current_serving_side', 'L');

        // 4. Record Rally - Receiver Wins (Hand-Out to Muneeb)
        $handoutResponse = $this->withHeaders($headers)->postJson("/api/v1/player/matches/{$this->match->id}/rally", [
            'call_type' => 'tin',
            'awarded_to_player_id' => $this->player2->id,
            'striker_player_id' => $this->player1->id,
            'handout_chosen_side' => 'R',
        ]);

        $handoutResponse->assertStatus(200)
            ->assertJsonPath('data.player_two.current_game_score', 1)
            ->assertJsonPath('data.current_server_id', $this->player2->id)
            ->assertJsonPath('data.current_serving_side', 'R')
            ->assertJsonPath('data.can_change_serving_side', true);

        // 5. Test Undo
        $undoResponse = $this->withHeaders($headers)->postJson("/api/v1/player/matches/{$this->match->id}/undo");

        $undoResponse->assertStatus(200)
            ->assertJsonPath('data.player_two.current_game_score', 0)
            ->assertJsonPath('data.current_server_id', $this->player1->id)
            ->assertJsonPath('data.current_serving_side', 'L');

        // 6. Premature Completion Attempt (Should fail because 0 games completed out of Best of 3)
        $prematureResponse = $this->withHeaders($headers)->postJson("/api/v1/player/matches/{$this->match->id}/complete", [
            'winner_player_id' => $this->player1->id,
        ]);

        $prematureResponse->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'MATCH_NOT_FINISHED');

        // 7. Complete 2 games for Player 1 (Usama) to satisfy Best of 3 rule
        for ($g = 1; $g <= 2; $g++) {
            for ($p = 0; $p < 11; $p++) {
                $this->withHeaders($headers)->postJson("/api/v1/player/matches/{$this->match->id}/rally", [
                    'call_type' => 'clean_winner',
                    'awarded_to_player_id' => $this->player1->id,
                ]);
            }
        }

        // 8. Live Match State
        $liveResponse = $this->withHeaders($headers)->getJson("/api/v1/player/matches/{$this->match->id}/live");

        $liveResponse->assertStatus(200)
            ->assertJsonPath('data.match_id', $this->match->id)
            ->assertJsonPath('data.player_one.games_won', 2);

        // 9. Finalize Match (Now succeeds)
        $completeResponse = $this->withHeaders($headers)->postJson("/api/v1/player/matches/{$this->match->id}/complete", [
            'winner_player_id' => $this->player1->id,
        ]);

        $completeResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.winner_player_id', $this->player1->id);
    }
}

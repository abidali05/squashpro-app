<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tournament;
use App\Models\TournamentInvitation;
use App\Models\TournamentTeam;
use App\Models\TournamentTeamPlayer;
use App\Models\TournamentGroup;
use App\Models\TournamentFixture;
use App\Models\TournamentMatch;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TournamentFixtureTest extends TestCase
{
    use RefreshDatabase;

    private User $hostClub;
    private User $opponentClub1;
    private User $opponentClub2;
    private User $unauthorizedClub;
    private string $hostToken;
    private string $unauthToken;

    private User $hostPlayer1;
    private User $hostPlayer2;
    private User $oppPlayer1;
    private User $oppPlayer2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        // Create clubs
        $this->hostClub = User::factory()->create(['role' => 'club', 'status' => 'active', 'club_name' => 'Host Club']);
        $this->opponentClub1 = User::factory()->create(['role' => 'club', 'status' => 'active', 'club_name' => 'Opponent Club 1']);
        $this->opponentClub2 = User::factory()->create(['role' => 'club', 'status' => 'active', 'club_name' => 'Opponent Club 2']);
        $this->unauthorizedClub = User::factory()->create(['role' => 'club', 'status' => 'active', 'club_name' => 'Unauthorized Club']);

        $this->hostToken = 'token-host-123';
        $this->hostClub->api_access_token = hash('sha256', $this->hostToken);
        $this->hostClub->save();

        $this->unauthToken = 'token-unauth-123';
        $this->unauthorizedClub->api_access_token = hash('sha256', $this->unauthToken);
        $this->unauthorizedClub->save();

        // Create players
        $this->hostPlayer1 = User::factory()->create(['role' => 'player', 'status' => 'active']);
        $this->hostPlayer2 = User::factory()->create(['role' => 'player', 'status' => 'active']);
        $this->oppPlayer1 = User::factory()->create(['role' => 'player', 'status' => 'active']);
        $this->oppPlayer2 = User::factory()->create(['role' => 'player', 'status' => 'active']);
    }

    private function setupRosters(Tournament $tournament): void
    {
        // Host team
        $hostTeam = TournamentTeam::create([
            'tournament_id' => $tournament->id,
            'club_id' => $this->hostClub->id,
            'submission_status' => 'submitted',
        ]);
        TournamentTeamPlayer::create(['team_id' => $hostTeam->id, 'player_id' => $this->hostPlayer1->id, 'position' => 1]);
        TournamentTeamPlayer::create(['team_id' => $hostTeam->id, 'player_id' => $this->hostPlayer2->id, 'position' => 2]);

        // Opponent team
        $oppTeam = TournamentTeam::create([
            'tournament_id' => $tournament->id,
            'club_id' => $this->opponentClub1->id,
            'submission_status' => 'submitted',
        ]);
        TournamentTeamPlayer::create(['team_id' => $oppTeam->id, 'player_id' => $this->oppPlayer1->id, 'position' => 1]);
        TournamentTeamPlayer::create(['team_id' => $oppTeam->id, 'player_id' => $this->oppPlayer2->id, 'position' => 2]);
    }

    public function test_host_club_can_store_and_retrieve_league_fixtures_successfully(): void
    {
        $tournament = Tournament::create([
            'club_id' => $this->hostClub->id,
            'opponent_club_id' => [$this->opponentClub1->id],
            'name' => 'Championship League',
            'format' => 'League',
            'tournament_type' => 'CLUB_TO_CLUB',
            'gender' => 'OPEN',
            'player_level' => ['BEGINNER'],
            'age_group' => '15-35',
            'maximum_players' => 10,
            'status' => 'open',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'registration_deadline' => '2026-08-15T18:00:00Z',
        ]);

        // Accept invitation
        TournamentInvitation::create([
            'tournament_id' => $tournament->id,
            'invited_club_id' => $this->opponentClub1->id,
            'status' => 'accepted'
        ]);

        $this->setupRosters($tournament);

        $payload = [
            'format' => 'league',
            'group_count' => 1,
            'groups' => [
                [
                    'group_name' => 'League',
                    'club_ids' => [$this->hostClub->id, $this->opponentClub1->id],
                    'fixtures' => [
                        [
                            'round' => 'League',
                            'home_club_id' => $this->hostClub->id,
                            'away_club_id' => $this->opponentClub1->id,
                            'matches' => [
                                [
                                    'sequence' => 1,
                                    'home_player_id' => $this->hostPlayer1->id,
                                    'away_player_id' => $this->oppPlayer1->id
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Store fixtures
        $response = $this->postJson(
            "/api/v1/club/tournaments/{$tournament->id}/fixtures",
            $payload,
            ['Authorization' => "Bearer {$this->hostToken}"]
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('tournament_groups', [
            'tournament_id' => $tournament->id,
            'name' => 'League'
        ]);

        $this->assertDatabaseHas('tournament_fixtures', [
            'tournament_id' => $tournament->id,
            'home_club_id' => $this->hostClub->id,
            'away_club_id' => $this->opponentClub1->id
        ]);

        // Get fixtures
        $getResponse = $this->getJson(
            "/api/v1/club/tournaments/{$tournament->id}/fixtures",
            ['Authorization' => "Bearer {$this->hostToken}"]
        );

        $getResponse->assertStatus(200);
        $getResponse->assertJsonPath('data.format', 'league');
        $getResponse->assertJsonCount(1, 'data.groups');
        $getResponse->assertJsonPath('data.groups.0.group_name', 'League');
        $getResponse->assertJsonPath('data.groups.0.fixtures.0.round', 'League');
        $getResponse->assertJsonPath('data.groups.0.fixtures.0.matches.0.home_player.full_name', $this->hostPlayer1->name);
    }

    public function test_store_fixtures_validates_unauthorized_club(): void
    {
        $tournament = Tournament::create([
            'club_id' => $this->hostClub->id,
            'name' => 'Championship League',
            'format' => 'League',
            'tournament_type' => 'CLUB_TO_CLUB',
            'gender' => 'OPEN',
            'player_level' => ['BEGINNER'],
            'age_group' => '15-35',
            'maximum_players' => 10,
            'status' => 'open',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'registration_deadline' => '2026-08-15T18:00:00Z',
        ]);

        $payload = [
            'format' => 'league',
            'group_count' => 1,
            'groups' => []
        ];

        $response = $this->postJson(
            "/api/v1/club/tournaments/{$tournament->id}/fixtures",
            $payload,
            ['Authorization' => "Bearer {$this->unauthToken}"]
        );

        $response->assertStatus(403);
        $response->assertJsonPath('error_code', 'FORBIDDEN');
    }

    public function test_store_fixtures_validates_single_league_group_name(): void
    {
        $tournament = Tournament::create([
            'club_id' => $this->hostClub->id,
            'name' => 'Championship League',
            'format' => 'League',
            'tournament_type' => 'CLUB_TO_CLUB',
            'gender' => 'OPEN',
            'player_level' => ['BEGINNER'],
            'age_group' => '15-35',
            'maximum_players' => 10,
            'status' => 'open',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'registration_deadline' => '2026-08-15T18:00:00Z',
        ]);

        $this->setupRosters($tournament);

        $payload = [
            'format' => 'league',
            'group_count' => 1,
            'groups' => [
                [
                    'group_name' => 'InvalidName',
                    'club_ids' => [$this->hostClub->id],
                    'fixtures' => []
                ]
            ]
        ];

        $response = $this->postJson(
            "/api/v1/club/tournaments/{$tournament->id}/fixtures",
            $payload,
            ['Authorization' => "Bearer {$this->hostToken}"]
        );

        $response->assertStatus(422);
        $response->assertJsonPath('error_code', 'VALIDATION_ERROR');
    }

    public function test_store_fixtures_validates_equal_group_sizes(): void
    {
        $tournament = Tournament::create([
            'club_id' => $this->hostClub->id,
            'opponent_club_id' => [$this->opponentClub1->id, $this->opponentClub2->id],
            'name' => 'Championship League',
            'format' => 'League',
            'tournament_type' => 'CLUB_TO_CLUB',
            'gender' => 'OPEN',
            'player_level' => ['BEGINNER'],
            'age_group' => '15-35',
            'maximum_players' => 10,
            'status' => 'open',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'registration_deadline' => '2026-08-15T18:00:00Z',
        ]);

        TournamentInvitation::create(['tournament_id' => $tournament->id, 'invited_club_id' => $this->opponentClub1->id, 'status' => 'accepted']);
        TournamentInvitation::create(['tournament_id' => $tournament->id, 'invited_club_id' => $this->opponentClub2->id, 'status' => 'accepted']);

        $this->setupRosters($tournament);

        $payload = [
            'format' => 'league',
            'group_count' => 2,
            'groups' => [
                [
                    'group_name' => 'Group A',
                    'club_ids' => [$this->hostClub->id, $this->opponentClub1->id],
                    'fixtures' => []
                ],
                [
                    'group_name' => 'Group B',
                    'club_ids' => [$this->opponentClub2->id],
                    'fixtures' => []
                ]
            ]
        ];

        $response = $this->postJson(
            "/api/v1/club/tournaments/{$tournament->id}/fixtures",
            $payload,
            ['Authorization' => "Bearer {$this->hostToken}"]
        );

        $response->assertStatus(422);
        $response->assertJsonPath('error_code', 'VALIDATION_ERROR');
    }

    public function test_store_fixtures_validates_player_roster_belonging(): void
    {
        $tournament = Tournament::create([
            'club_id' => $this->hostClub->id,
            'opponent_club_id' => [$this->opponentClub1->id],
            'name' => 'Championship League',
            'format' => 'League',
            'tournament_type' => 'CLUB_TO_CLUB',
            'gender' => 'OPEN',
            'player_level' => ['BEGINNER'],
            'age_group' => '15-35',
            'maximum_players' => 10,
            'status' => 'open',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'registration_deadline' => '2026-08-15T18:00:00Z',
        ]);

        TournamentInvitation::create(['tournament_id' => $tournament->id, 'invited_club_id' => $this->opponentClub1->id, 'status' => 'accepted']);
        $this->setupRosters($tournament);

        $payload = [
            'format' => 'league',
            'group_count' => 1,
            'groups' => [
                [
                    'group_name' => 'League',
                    'club_ids' => [$this->hostClub->id, $this->opponentClub1->id],
                    'fixtures' => [
                        [
                            'round' => 'League',
                            'home_club_id' => $this->hostClub->id,
                            'away_club_id' => $this->opponentClub1->id,
                            'matches' => [
                                [
                                    'sequence' => 1,
                                    'home_player_id' => $this->oppPlayer1->id, // WRONG: Opponent player assigned to home club!
                                    'away_player_id' => $this->oppPlayer2->id
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson(
            "/api/v1/club/tournaments/{$tournament->id}/fixtures",
            $payload,
            ['Authorization' => "Bearer {$this->hostToken}"]
        );

        $response->assertStatus(422);
        $response->assertJsonPath('error_code', 'VALIDATION_ERROR');
    }

    public function test_host_club_can_store_and_retrieve_knockout_fixtures_successfully(): void
    {
        $tournament = Tournament::create([
            'club_id' => $this->hostClub->id,
            'opponent_club_id' => [$this->opponentClub1->id],
            'name' => 'Knockout Cup',
            'format' => 'Knockout',
            'tournament_type' => 'CLUB_TO_CLUB',
            'gender' => 'OPEN',
            'player_level' => ['BEGINNER'],
            'age_group' => '15-35',
            'maximum_players' => 10,
            'status' => 'open',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'registration_deadline' => '2026-08-15T18:00:00Z',
        ]);

        TournamentInvitation::create(['tournament_id' => $tournament->id, 'invited_club_id' => $this->opponentClub1->id, 'status' => 'accepted']);
        $this->setupRosters($tournament);

        $payload = [
            'format' => 'knockout',
            'group_count' => null,
            'groups' => [],
            'fixtures' => [
                [
                    'round' => 'Round 1',
                    'home_club_id' => $this->hostClub->id,
                    'away_club_id' => $this->opponentClub1->id,
                    'matches' => [
                        [
                            'sequence' => 1,
                            'home_player_id' => $this->hostPlayer1->id,
                            'away_player_id' => $this->oppPlayer1->id
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson(
            "/api/v1/club/tournaments/{$tournament->id}/fixtures",
            $payload,
            ['Authorization' => "Bearer {$this->hostToken}"]
        );

        $response->assertStatus(200);

        // Get fixtures
        $getResponse = $this->getJson(
            "/api/v1/club/tournaments/{$tournament->id}/fixtures",
            ['Authorization' => "Bearer {$this->hostToken}"]
        );

        $getResponse->assertStatus(200);
        $getResponse->assertJsonPath('data.format', 'knockout');
        $getResponse->assertJsonPath('data.group_count', null);
        $getResponse->assertJsonCount(1, 'data.fixtures');
        $getResponse->assertJsonPath('data.fixtures.0.round', 'Round 1');
    }
}

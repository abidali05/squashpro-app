<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Tournament;
use App\Models\TournamentInvitation;
use App\Models\TournamentTeam;
use App\Models\TournamentTeamPlayer;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerOfficialTournamentTest extends TestCase
{
    use RefreshDatabase;

    private User $club;
    private User $officialPlayer;
    private string $officialToken;
    private User $unauthorizedPlayer;
    private string $unauthToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        // Host Club
        $this->club = User::factory()->create(['role' => 'club', 'status' => 'active', 'club_name' => 'Lahore Squash']);

        // Official player
        $this->officialPlayer = User::factory()->create(['role' => 'player', 'status' => 'active']);
        $this->officialPlayer->assignRole('player');
        $this->officialToken = 'token-official-player';
        $this->officialPlayer->api_access_token = hash('sha256', $this->officialToken);
        $this->officialPlayer->save();

        // Unauthorized player
        $this->unauthorizedPlayer = User::factory()->create(['role' => 'player', 'status' => 'active']);
        $this->unauthorizedPlayer->assignRole('player');
        $this->unauthToken = 'token-unauthorized-player';
        $this->unauthorizedPlayer->api_access_token = hash('sha256', $this->unauthToken);
        $this->unauthorizedPlayer->save();
    }

    public function test_date_parameter_is_validated_in_official_tournaments(): void
    {
        // 1. Missing date query parameter
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->officialToken)
            ->getJson('/api/v1/player/official-tournaments');
        $response->assertStatus(422);
        $response->assertJsonPath('error_code', 'VALIDATION_ERROR');

        // 2. Invalid date format
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->officialToken)
            ->getJson('/api/v1/player/official-tournaments?date=11-08-2026');
        $response->assertStatus(422);
        $response->assertJsonPath('error_code', 'VALIDATION_ERROR');
    }

    public function test_player_can_fetch_assigned_tournaments_on_matching_date(): void
    {
        $tournament1 = Tournament::create([
            'club_id' => $this->club->id,
            'name' => 'Summer Cup Day 1',
            'format' => 'Knockout',
            'tournament_type' => 'CLUB_TO_CLUB',
            'gender' => 'OPEN',
            'player_level' => ['BEGINNER'],
            'age_group' => '15-35',
            'maximum_players' => 10,
            'status' => 'open',
            'start_date' => '2026-08-11',
            'end_date' => '2026-08-11',
            'registration_deadline' => '2026-08-10T18:00:00Z',
        ]);

        $tournament2 = Tournament::create([
            'club_id' => $this->club->id,
            'name' => 'Summer Cup Day 2',
            'format' => 'Knockout',
            'tournament_type' => 'CLUB_TO_CLUB',
            'gender' => 'OPEN',
            'player_level' => ['BEGINNER'],
            'age_group' => '15-35',
            'maximum_players' => 10,
            'status' => 'open',
            'start_date' => '2026-08-12',
            'end_date' => '2026-08-12',
            'registration_deadline' => '2026-08-10T18:00:00Z',
        ]);

        // Assign to tournament 1 as both Scorer and Umpire
        $tournament1->scorers()->attach($this->officialPlayer->id);
        $tournament1->umpires()->attach($this->officialPlayer->id);

        // Fetch for 2026-08-11
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->officialToken)
            ->getJson('/api/v1/player/official-tournaments?date=2026-08-11');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $tournament1->id);
        $response->assertJsonPath('data.0.assigned_roles', ['scorer', 'umpire']);

        // Fetch for 2026-08-12 (not assigned to tournament 2)
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->officialToken)
            ->getJson('/api/v1/player/official-tournaments?date=2026-08-12');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_assigned_official_can_view_tournament_detail(): void
    {
        $tournament = Tournament::create([
            'club_id' => $this->club->id,
            'name' => 'Summer Cup Detail Check',
            'format' => 'Knockout',
            'tournament_type' => 'CLUB_TO_CLUB',
            'gender' => 'OPEN',
            'player_level' => ['BEGINNER'],
            'age_group' => '15-35',
            'maximum_players' => 10,
            'status' => 'open',
            'start_date' => '2026-08-11',
            'end_date' => '2026-08-11',
            'registration_deadline' => '2026-08-10T18:00:00Z',
        ]);

        $tournament->scorers()->attach($this->officialPlayer->id);

        // 1. Authorized access
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->officialToken)
            ->getJson("/api/v1/player/official-tournaments/{$tournament->id}");
        $response->assertOk();
        $response->assertJsonPath('data.tournament_id', $tournament->id);
        $response->assertJsonPath('data.auth_player_roles', ['scorer']);

        // 2. Unauthorized access
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->unauthToken)
            ->getJson("/api/v1/player/official-tournaments/{$tournament->id}");
        $response->assertStatus(403);
        $response->assertJsonPath('error_code', 'ACCESS_DENIED');
    }

    public function test_assigned_official_can_get_fixtures(): void
    {
        $tournament = Tournament::create([
            'club_id' => $this->club->id,
            'name' => 'Fixtures Check',
            'format' => 'Knockout',
            'tournament_type' => 'CLUB_TO_CLUB',
            'gender' => 'OPEN',
            'player_level' => ['BEGINNER'],
            'age_group' => '15-35',
            'maximum_players' => 10,
            'status' => 'open',
            'start_date' => '2026-08-11',
            'end_date' => '2026-08-11',
            'registration_deadline' => '2026-08-10T18:00:00Z',
        ]);

        $tournament->umpires()->attach($this->officialPlayer->id);

        // 1. Authorized access
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->officialToken)
            ->getJson("/api/v1/player/official-tournaments/{$tournament->id}/fixtures");
        $response->assertOk();

        // 2. Unauthorized access
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->unauthToken)
            ->getJson("/api/v1/player/official-tournaments/{$tournament->id}/fixtures");
        $response->assertStatus(403);
        $response->assertJsonPath('error_code', 'ACCESS_DENIED');
    }
}

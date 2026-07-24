<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Tournament;
use App\Models\ClubMembership;
use App\Models\ClubMembershipRequest;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $club1;
    private User $club2;
    private string $clubToken;

    private User $player1;
    private string $playerToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        // Setup club1
        $this->club1 = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'club_name' => 'Club Alpha',
        ]);
        $plainClubToken = 'test-club-token-999';
        $this->club1->api_access_token = hash('sha256', $plainClubToken);
        $this->club1->save();
        $this->clubToken = $plainClubToken;

        // Setup club2
        $this->club2 = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'club_name' => 'Club Beta',
        ]);

        // Setup player1
        $this->player1 = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'gender' => 'male',
            'playing_level' => 'intermediate',
            'dob' => '2006-01-01',
        ]);
        $plainPlayerToken = 'test-player-token-999';
        $this->player1->api_access_token = hash('sha256', $plainPlayerToken);
        $this->player1->save();
        $this->playerToken = $plainPlayerToken;
    }

    public function test_club_dashboard_returns_pending_metrics(): void
    {
        // Seed 1 pending membership request
        ClubMembershipRequest::create([
            'club_id' => $this->club1->id,
            'player_id' => $this->player1->id,
            'membership_number' => 'PENDING',
            'status' => 'pending',
        ]);

        // Seed 1 pending tournament invitation (club2 inviting club1)
        Tournament::create([
            'club_id' => $this->club2->id,
            'opponent_club_id' => $this->club1->id,
            'name' => 'Inter-Club Cup',
            'format' => 'Knockout',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'registration_deadline' => '2026-08-15T18:00:00Z',
            'tournament_type' => 'CLUB_TO_CLUB',
            'gender' => 'OPEN',
            'player_level' => ['INTERMEDIATE'],
            'age_group' => '15-45',
            'maximum_players' => 10,
            'status' => 'pending',
        ]);

        $response = $this->getJson(
            '/api/v1/club/dashboard',
            ['Authorization' => "Bearer {$this->clubToken}"]
        );

        $response->assertOk();
        $response->assertJsonPath('data.pending_membership_requests', 1);
        $response->assertJsonPath('data.pending_tournament_invitations', 1);
    }

    public function test_player_dashboard_respects_tournament_visibility(): void
    {
        // Create a members-only tournament at club1 (player1 is NOT a member of club1 yet)
        $tournament1 = Tournament::create([
            'club_id' => $this->club1->id,
            'name' => 'Internal Derby',
            'format' => 'Round Robin',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-12',
            'registration_deadline' => '2026-09-05T18:00:00Z',
            'tournament_type' => 'CLUB_MEMBERS_ONLY',
            'gender' => 'MALE',
            'player_level' => ['INTERMEDIATE'],
            'age_group' => '15-25',
            'maximum_players' => 10,
            'status' => 'open',
        ]);

        // Dashboard request as player1 -> should not return tournament1
        $response1 = $this->getJson(
            '/api/v1/player/dashboard',
            ['Authorization' => "Bearer {$this->playerToken}"]
        );
        $response1->assertOk();
        $this->assertCount(0, $response1->json('data.active_tournaments'));

        // Link player1 to club1 as approved member
        ClubMembership::create([
            'club_id' => $this->club1->id,
            'player_id' => $this->player1->id,
            'membership_number' => 'CSC-A01',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        // Dashboard request as player1 -> should now return tournament1
        $response2 = $this->getJson(
            '/api/v1/player/dashboard',
            ['Authorization' => "Bearer {$this->playerToken}"]
        );
        $response2->assertOk();
        $this->assertCount(1, $response2->json('data.active_tournaments'));
        $this->assertEquals($tournament1->id, $response2->json('data.active_tournaments.0.id'));
    }
}

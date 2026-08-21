<?php

namespace Tests\Feature\Api\V1;

use App\Models\ClubMembership;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetPlayerClubsTest extends TestCase
{
    use RefreshDatabase;

    private User $player;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        // Create player user
        $this->player = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'name' => 'Test Player',
        ]);
        $this->player->assignRole('player');

        // Setup API token
        $plainToken = 'test-player-api-token-12345';
        $this->player->api_access_token = hash('sha256', $plainToken);
        $this->player->save();
        $this->token = $plainToken;
    }

    public function test_get_player_clubs_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/player/get-player-clubs');
        $response->assertStatus(401);
    }

    public function test_get_player_clubs_requires_player_role(): void
    {
        // Create a club user with a valid token
        $club = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
        ]);
        $club->assignRole('club');
        $clubToken = 'test-club-token-123';
        $club->api_access_token = hash('sha256', $clubToken);
        $club->save();

        $response = $this->withHeader('Authorization', 'Bearer ' . $clubToken)
            ->getJson('/api/v1/player/get-player-clubs');

        $response->assertStatus(403);
    }

    public function test_get_player_clubs_returns_only_approved_memberships_of_active_clubs(): void
    {
        // 1. Approved membership with active club
        $activeClub = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'club_name' => 'Active Squash Club',
        ]);
        $activeClub->assignRole('club');

        ClubMembership::create([
            'club_id' => $activeClub->id,
            'player_id' => $this->player->id,
            'membership_number' => 'MEM-001',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        // 2. Pending membership with active club (should not return)
        $pendingClub = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'club_name' => 'Pending Club',
        ]);
        $pendingClub->assignRole('club');

        ClubMembership::create([
            'club_id' => $pendingClub->id,
            'player_id' => $this->player->id,
            'membership_number' => 'MEM-002',
            'status' => 'pending',
        ]);

        // 3. Approved membership with suspended club (should not return)
        $suspendedClub = User::factory()->create([
            'role' => 'club',
            'status' => 'suspended',
            'club_name' => 'Suspended Club',
        ]);
        $suspendedClub->assignRole('club');

        ClubMembership::create([
            'club_id' => $suspendedClub->id,
            'player_id' => $this->player->id,
            'membership_number' => 'MEM-003',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/player/get-player-clubs');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.club_name', 'Active Squash Club')
            ->assertJsonPath('data.0.membership_number', 'MEM-001')
            ->assertJsonPath('data.0.membership_status', 'approved');
    }

    public function test_get_player_clubs_supports_searching(): void
    {
        $club1 = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'club_name' => 'Islamabad Squash Academy',
        ]);
        $club1->assignRole('club');

        ClubMembership::create([
            'club_id' => $club1->id,
            'player_id' => $this->player->id,
            'membership_number' => 'ISA-1122',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $club2 = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'club_name' => 'Lahore Squash Court',
        ]);
        $club2->assignRole('club');

        ClubMembership::create([
            'club_id' => $club2->id,
            'player_id' => $this->player->id,
            'membership_number' => 'LSC-5566',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        // Search by club name partial
        $responseName = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/player/get-player-clubs?search=Lahore');

        $responseName->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.club_name', 'Lahore Squash Court');

        // Search by membership number partial
        $responseNumber = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/player/get-player-clubs?search=1122');

        $responseNumber->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.club_name', 'Islamabad Squash Academy');

        // Search with no matches
        $responseEmpty = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/player/get-player-clubs?search=NonExistent');

        $responseEmpty->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_get_player_clubs_supports_pagination(): void
    {
        // Create 3 active clubs with memberships
        for ($i = 1; $i <= 3; $i++) {
            $club = User::factory()->create([
                'role' => 'club',
                'status' => 'active',
                'club_name' => 'Squash Club ' . $i,
            ]);
            $club->assignRole('club');

            ClubMembership::create([
                'club_id' => $club->id,
                'player_id' => $this->player->id,
                'membership_number' => 'MEM-00' . $i,
                'status' => 'approved',
                'approved_at' => now(),
            ]);
        }

        // Fetch page 1 with per_page = 2
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/player/get-player-clubs?page=1&per_page=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.per_page', 2)
            ->assertJsonPath('pagination.total', 3)
            ->assertJsonPath('pagination.last_page', 2);

        // Fetch page 2 with per_page = 2
        $response2 = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/player/get-player-clubs?page=2&per_page=2');

        $response2->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('pagination.current_page', 2)
            ->assertJsonPath('pagination.per_page', 2)
            ->assertJsonPath('pagination.total', 3)
            ->assertJsonPath('pagination.last_page', 2);
    }

    public function test_get_player_clubs_validation(): void
    {
        // per_page exceeds max (50) -> will be capped at 50, but validation allows up to 50
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/player/get-player-clubs?per_page=100');

        // Note: our Form Request has validation max:50, so this should fail validation (422)
        $response->assertStatus(422)
            ->assertJsonPath('message', 'Validation failed.');
    }
}

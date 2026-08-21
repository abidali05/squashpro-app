<?php

namespace Tests\Feature\Api\V1;

use App\Models\AuditLog;
use App\Models\ClubMembership;
use App\Models\ClubMembershipRequest;
use App\Models\User;
use App\Notifications\Club\NewMembershipRequestNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AddPlayerClubTest extends TestCase
{
    use RefreshDatabase;

    private User $player;
    private string $token;
    private User $club;

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

        // Setup player API token
        $plainToken = 'test-player-api-token-12345';
        $this->player->api_access_token = hash('sha256', $plainToken);
        $this->player->save();
        $this->token = $plainToken;

        // Create an active club user
        $this->club = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'club_name' => 'Super Squash Arena',
        ]);
        $this->club->assignRole('club');
    }

    public function test_add_player_club_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/player/add-player-club', [
            'club_id' => $this->club->id,
            'membership_number' => 'MEM-123',
        ]);

        $response->assertStatus(401);
    }

    public function test_add_player_club_requires_player_role(): void
    {
        // Create club user token
        $anotherClub = User::factory()->create(['role' => 'club', 'status' => 'active']);
        $anotherClub->assignRole('club');
        $clubToken = 'club-token-456';
        $anotherClub->api_access_token = hash('sha256', $clubToken);
        $anotherClub->save();

        $response = $this->withHeader('Authorization', 'Bearer ' . $clubToken)
            ->postJson('/api/v1/player/add-player-club', [
                'club_id' => $this->club->id,
                'membership_number' => 'MEM-123',
            ]);

        $response->assertStatus(403);
    }

    public function test_add_player_club_submits_successfully_and_logs_audit_and_notifies(): void
    {
        Notification::fake();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/player/add-player-club', [
                'club_id' => $this->club->id,
                'membership_number' => 'MSC-22018',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 201)
            ->assertJsonPath('message', 'Club membership request submitted successfully')
            ->assertJsonPath('data.club_id', $this->club->id)
            ->assertJsonPath('data.membership_number', 'MSC-22018')
            ->assertJsonPath('data.membership_status', 'pending');

        // Assert database request has status pending
        $this->assertDatabaseHas('club_membership_requests', [
            'club_id' => $this->club->id,
            'player_id' => $this->player->id,
            'membership_number' => 'MSC-22018',
            'status' => 'pending',
        ]);

        // Assert audit log is written
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->player->id,
            'action' => 'request_membership',
            'entity_type' => ClubMembershipRequest::class,
        ]);

        // Assert notification was sent to the club
        Notification::assertSentTo(
            $this->club,
            NewMembershipRequestNotification::class,
            function ($notification) {
                return $notification->toArray($this->club)['data']['membership_number'] === 'MSC-22018';
            }
        );
    }

    public function test_add_player_club_prevents_duplicate_when_already_approved(): void
    {
        // Setup approved membership
        ClubMembership::create([
            'club_id' => $this->club->id,
            'player_id' => $this->player->id,
            'membership_number' => 'MSC-22018',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/player/add-player-club', [
                'club_id' => $this->club->id,
                'membership_number' => 'MSC-22018',
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Membership already exists or request already pending')
            ->assertJsonPath('error_code', 'MEMBERSHIP_ALREADY_EXISTS');
    }

    public function test_add_player_club_prevents_duplicate_when_request_is_pending(): void
    {
        // Setup pending request
        ClubMembershipRequest::create([
            'club_id' => $this->club->id,
            'player_id' => $this->player->id,
            'membership_number' => 'MSC-22018',
            'status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/player/add-player-club', [
                'club_id' => $this->club->id,
                'membership_number' => 'MSC-22018',
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Membership already exists or request already pending')
            ->assertJsonPath('error_code', 'MEMBERSHIP_ALREADY_EXISTS');
    }

    public function test_add_player_club_validates_required_fields(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/player/add-player-club', []);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonValidationErrors(['club_id', 'membership_number']);
    }

    public function test_add_player_club_rejects_inactive_club(): void
    {
        // Create suspended club
        $suspendedClub = User::factory()->create([
            'role' => 'club',
            'status' => 'suspended',
        ]);
        $suspendedClub->assignRole('club');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/player/add-player-club', [
                'club_id' => $suspendedClub->id,
                'membership_number' => 'MSC-22018',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonValidationErrors(['club_id']);
    }
}

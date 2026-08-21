<?php

namespace Tests\Feature\Api\V1;

use App\Models\AuditLog;
use App\Models\ClubMembership;
use App\Models\User;
use App\Notifications\Club\PlayerLeftClubNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RemovePlayerClubTest extends TestCase
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

    public function test_remove_player_club_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/player/remove-player-club', [
            'club_id' => $this->club->id,
            'reason' => 'Leaving.',
        ]);

        $response->assertStatus(401);
    }

    public function test_remove_player_club_requires_player_role(): void
    {
        // Create club user token
        $anotherClub = User::factory()->create(['role' => 'club', 'status' => 'active']);
        $anotherClub->assignRole('club');
        $clubToken = 'club-token-456';
        $anotherClub->api_access_token = hash('sha256', $clubToken);
        $anotherClub->save();

        $response = $this->withHeader('Authorization', 'Bearer ' . $clubToken)
            ->postJson('/api/v1/player/remove-player-club', [
                'club_id' => $this->club->id,
                'reason' => 'Leaving.',
            ]);

        $response->assertStatus(403);
    }

    public function test_remove_player_club_submits_successfully_and_logs_audit_and_notifies(): void
    {
        Notification::fake();

        // Create approved membership first
        $membership = ClubMembership::create([
            'club_id' => $this->club->id,
            'player_id' => $this->player->id,
            'membership_number' => 'MSC-22018',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/player/remove-player-club', [
                'club_id' => $this->club->id,
                'reason' => 'I am no longer a member of this club.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 200)
            ->assertJsonPath('message', 'Club membership removed successfully')
            ->assertJsonPath('data.club_id', $this->club->id)
            ->assertJsonPath('data.membership_status', 'removed')
            ->assertJsonPath('data.reason', 'I am no longer a member of this club.');

        // Assert database record has status removed and reason and removed_at
        $this->assertDatabaseHas('club_memberships', [
            'id' => $membership->id,
            'status' => 'removed',
            'removal_reason' => 'I am no longer a member of this club.',
        ]);
        $this->assertNotNull($membership->fresh()->removed_at);

        // Assert audit log is written
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->player->id,
            'action' => 'remove_membership',
            'entity_type' => ClubMembership::class,
            'entity_id' => $membership->id,
        ]);

        // Assert notification was sent to the club
        Notification::assertSentTo(
            $this->club,
            PlayerLeftClubNotification::class,
            function ($notification) {
                $arr = $notification->toArray($this->club);
                return $arr['data']['reason'] === 'I am no longer a member of this club.' &&
                       $arr['data']['player_id'] === $this->player->id;
            }
        );
    }

    public function test_remove_player_club_validates_required_fields(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/player/remove-player-club', []);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonValidationErrors(['club_id', 'reason']);
    }

    public function test_remove_player_club_rejects_non_existent_membership(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/player/remove-player-club', [
                'club_id' => $this->club->id, // No membership exists
                'reason' => 'Leaving.',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonValidationErrors(['club_id']);
    }

    public function test_remove_player_club_rejects_already_removed_membership(): void
    {
        // Create already removed membership
        ClubMembership::create([
            'club_id' => $this->club->id,
            'player_id' => $this->player->id,
            'membership_number' => 'MSC-22018',
            'status' => 'removed',
            'approved_at' => now(),
            'removed_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/player/remove-player-club', [
                'club_id' => $this->club->id,
                'reason' => 'Leaving.',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonValidationErrors(['club_id']);
    }
}

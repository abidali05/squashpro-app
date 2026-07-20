<?php

namespace Tests\Feature\Api\V1;

use App\Models\AuditLog;
use App\Models\ClubMembership;
use App\Models\ClubMembershipRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubMemberManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $club;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        // Create club user
        $this->club = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'club_name' => 'Verify Squash Club',
        ]);
        $this->club->assignRole('club');

        // Setup API token
        $plainToken = 'test-api-token-12345';
        $this->club->api_access_token = hash('sha256', $plainToken);
        $this->club->save();
        $this->token = $plainToken;
    }

    public function test_list_club_members(): void
    {
        $player1 = User::factory()->create(['role' => 'player', 'status' => 'active', 'name' => 'Babar Azam']);
        $player2 = User::factory()->create(['role' => 'player', 'status' => 'active', 'name' => 'Shaheen Afridi']);

        // Create memberships
        ClubMembership::create([
            'club_id' => $this->club->id,
            'player_id' => $player1->id,
            'membership_number' => 'CSC-101',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        ClubMembership::create([
            'club_id' => $this->club->id,
            'player_id' => $player2->id,
            'membership_number' => 'CSC-102',
            'status' => 'removed',
            'approved_at' => now(),
            'removed_at' => now(),
        ]);

        // Request approved members (default)
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/club/members');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.membership_number', 'CSC-101')
            ->assertJsonPath('data.0.player.full_name', 'Babar Azam');

        // Request removed members
        $responseRemoved = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/club/members?status=removed');

        $responseRemoved->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.membership_number', 'CSC-102')
            ->assertJsonPath('data.0.player.full_name', 'Shaheen Afridi');

        // Search members
        $responseSearch = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/club/members?search=Babar');

        $responseSearch->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_add_club_member_successfully_and_audit(): void
    {
        $player = User::factory()->create(['role' => 'player', 'status' => 'active', 'name' => 'Babar Azam']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/club/members', [
                'player_id' => $player->id,
                'membership_number' => 'CSC-200',
                'verification_mode' => 'club_confirmed',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.membership_number', 'CSC-200')
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('club_memberships', [
            'club_id' => $this->club->id,
            'player_id' => $player->id,
            'status' => 'approved',
            'membership_number' => 'CSC-200',
            'verification_mode' => 'club_confirmed',
        ]);

        // Assert audit log is written
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->club->id,
            'action' => 'add_member',
            'entity_type' => ClubMembership::class,
        ]);
    }

    public function test_add_club_member_prevents_duplicates(): void
    {
        $player = User::factory()->create(['role' => 'player', 'status' => 'active', 'name' => 'Babar Azam']);

        ClubMembership::create([
            'club_id' => $this->club->id,
            'player_id' => $player->id,
            'membership_number' => 'CSC-200',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/club/members', [
                'player_id' => $player->id,
                'membership_number' => 'CSC-201',
                'verification_mode' => 'club_confirmed',
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('error_code', 'MEMBERSHIP_ALREADY_EXISTS');
    }

    public function test_remove_club_member_successfully_and_audit(): void
    {
        $player = User::factory()->create(['role' => 'player', 'status' => 'active', 'name' => 'Babar Azam']);

        $membership = ClubMembership::create([
            'club_id' => $this->club->id,
            'player_id' => $player->id,
            'membership_number' => 'CSC-200',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/v1/club/members/{$membership->id}", [
                'reason' => 'Membership expired.',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('club_memberships', [
            'id' => $membership->id,
            'status' => 'removed',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->club->id,
            'action' => 'remove_member',
            'entity_type' => ClubMembership::class,
            'entity_id' => $membership->id,
        ]);
    }

    public function test_get_member_detail_endpoint(): void
    {
        $player = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'name' => 'Babar Azam',
            'dob' => '1995-10-15',
            'gender' => 'male',
            'playing_level' => 'advanced',
            'primary_hand' => 'right',
            'bio' => 'Top order batsman playing squash',
        ]);

        $membership = ClubMembership::create([
            'club_id' => $this->club->id,
            'player_id' => $player->id,
            'membership_number' => 'CSC-200',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/v1/club/members/{$membership->id}");

        $response->assertOk()
            ->assertJsonPath('data.membership_number', 'CSC-200')
            ->assertJsonPath('data.booking_eligible', true)
            ->assertJsonPath('data.player.full_name', 'Babar Azam')
            ->assertJsonPath('data.player.playing_level', 'advanced');
    }
}

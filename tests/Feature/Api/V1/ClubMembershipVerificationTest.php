<?php

namespace Tests\Feature\Api\V1;

use App\Models\ClubMembership;
use App\Models\ClubMembershipRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubMembershipVerificationTest extends TestCase
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

    public function test_list_membership_requests(): void
    {
        $player = User::factory()->create(['role' => 'player', 'status' => 'active', 'name' => 'Babar Azam']);

        ClubMembershipRequest::create([
            'club_id' => $this->club->id,
            'player_id' => $player->id,
            'membership_number' => 'CSC-999',
            'status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/club/membership-requests');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.membership_number', 'CSC-999')
            ->assertJsonPath('data.0.player.full_name', 'Babar Azam')
            ->assertJsonPath('data.0.player.first_name', 'Babar')
            ->assertJsonPath('data.0.player.last_name', 'Azam');
    }

    public function test_approve_membership_request_and_idempotency(): void
    {
        $player = User::factory()->create(['role' => 'player', 'status' => 'active', 'name' => 'Babar Azam']);

        $request = ClubMembershipRequest::create([
            'club_id' => $this->club->id,
            'player_id' => $player->id,
            'membership_number' => 'CSC-999',
            'status' => 'pending',
        ]);

        // 1. First approval
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/v1/club/membership-requests/{$request->id}/approve", [
                'notes' => 'Verified',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.membership_number', 'CSC-999')
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('club_membership_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'reviewed_by' => $this->club->id,
        ]);

        $this->assertDatabaseHas('club_memberships', [
            'club_id' => $this->club->id,
            'player_id' => $player->id,
            'status' => 'approved',
            'membership_number' => 'CSC-999',
        ]);

        // Check notification is created in DB for player
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $player->id,
            'type' => 'membership_approved',
        ]);

        // 2. Second approval (Idempotency)
        $responseIdempotent = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/v1/club/membership-requests/{$request->id}/approve", [
                'notes' => 'Verified again',
            ]);

        $responseIdempotent->assertOk()
            ->assertJsonPath('data.membership_number', 'CSC-999');

        // Confirm there is still only 1 record in club_memberships
        $count = ClubMembership::where('club_id', $this->club->id)
            ->where('player_id', $player->id)
            ->count();
        $this->assertEquals(1, $count);
    }

    public function test_reject_membership_request(): void
    {
        $player = User::factory()->create(['role' => 'player', 'status' => 'active', 'name' => 'Babar Azam']);

        $request = ClubMembershipRequest::create([
            'club_id' => $this->club->id,
            'player_id' => $player->id,
            'membership_number' => 'CSC-999',
            'status' => 'pending',
        ]);

        // 1. Reject without reason -> validation fails (422)
        $responseNoReason = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/v1/club/membership-requests/{$request->id}/reject");

        $responseNoReason->assertStatus(422);

        // 2. Reject with reason -> succeeds (200)
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/v1/club/membership-requests/{$request->id}/reject", [
                'reason' => 'Invalid membership number',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('club_membership_requests', [
            'id' => $request->id,
            'status' => 'rejected',
            'rejection_reason' => 'Invalid membership number',
            'reviewed_by' => $this->club->id,
        ]);

        // Check notification is created in DB for player
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $player->id,
            'type' => 'membership_rejected',
        ]);
    }

    public function test_membership_approval_with_type_and_expiry(): void
    {
        $player = User::factory()->create(['role' => 'player', 'status' => 'active', 'name' => 'Mohammad Rizwan']);

        $request1 = ClubMembershipRequest::create([
            'club_id' => $this->club->id,
            'player_id' => $player->id,
            'membership_number' => 'CSC-888',
            'status' => 'pending',
        ]);

        // 1. Omit expiry date for temporary membership -> fails (422)
        $responseInvalid = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/v1/club/membership-requests/{$request1->id}/approve", [
                'membership_type' => 'temporary',
            ]);
        $responseInvalid->assertStatus(422)
            ->assertJsonValidationErrors(['membership_expiry_date']);

        // 2. Pass past expiry date -> fails (422)
        $responsePast = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/v1/club/membership-requests/{$request1->id}/approve", [
                'membership_type' => 'temporary',
                'membership_expiry_date' => '2020-01-01',
            ]);
        $responsePast->assertStatus(422)
            ->assertJsonValidationErrors(['membership_expiry_date']);

        // 3. Valid temporary membership -> succeeds (200)
        $expiry = now()->addDays(30)->startOfDay();
        $responseTemp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/v1/club/membership-requests/{$request1->id}/approve", [
                'membership_type' => 'temporary',
                'membership_expiry_date' => $expiry->toDateString(),
            ]);

        $responseTemp->assertOk()
            ->assertJsonPath('data.membership_type', 'temporary');

        $this->assertDatabaseHas('club_memberships', [
            'club_id' => $this->club->id,
            'player_id' => $player->id,
            'membership_type' => 'temporary',
            'membership_expiry_date' => $expiry->format('Y-m-d H:i:s'),
        ]);

        // 4. Permanent membership -> succeeds (200) and expiry is null
        $player2 = User::factory()->create(['role' => 'player', 'status' => 'active', 'name' => 'Fakhar Zaman']);
        $request2 = ClubMembershipRequest::create([
            'club_id' => $this->club->id,
            'player_id' => $player2->id,
            'membership_number' => 'CSC-777',
            'status' => 'pending',
        ]);

        $responsePerm = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/v1/club/membership-requests/{$request2->id}/approve", [
                'membership_type' => 'permanent',
            ]);

        $responsePerm->assertOk()
            ->assertJsonPath('data.membership_type', 'permanent')
            ->assertJsonPath('data.membership_expiry_date', null);

        $this->assertDatabaseHas('club_memberships', [
            'club_id' => $this->club->id,
            'player_id' => $player2->id,
            'membership_type' => 'permanent',
            'membership_expiry_date' => null,
        ]);
    }
}

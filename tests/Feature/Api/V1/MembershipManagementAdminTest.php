<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\ClubMembership;
use App\Models\ClubMembershipRequest;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipManagementAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $player;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        // Create admin user
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->admin->assignRole('admin');

        // Create player user
        $this->player = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->player->assignRole('player');
    }

    public function test_admin_can_access_memberships_index_page(): void
    {
        // Act as admin
        $response = $this->actingAs($this->admin)
            ->get('/admin/memberships');

        $response->assertOk();
        $response->assertViewIs('content.admin.memberships.index');
    }

    public function test_admin_can_access_membership_requests_index_page(): void
    {
        // Act as admin
        $response = $this->actingAs($this->admin)
            ->get('/admin/membership-requests');

        $response->assertOk();
        $response->assertViewIs('content.admin.memberships.requests');
    }

    public function test_player_cannot_access_memberships_pages(): void
    {
        // Act as player -> should get forbidden or redirect to login (auth middleware)
        $response1 = $this->actingAs($this->player)
            ->get('/admin/memberships');
        $response1->assertStatus(403);

        $response2 = $this->actingAs($this->player)
            ->get('/admin/membership-requests');
        $response2->assertStatus(403);
    }
}

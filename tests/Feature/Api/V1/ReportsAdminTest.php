<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsAdminTest extends TestCase
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
        ]);
    }

    public function test_admin_can_access_reports_index_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/reports');

        $response->assertOk();
        $response->assertViewIs('content.admin.reports.index');
        $response->assertSee('System Reports');
        $response->assertSee('Club Reports');
        $response->assertSee('Player Demographics');
        $response->assertSee('Booking');
        $response->assertSee('Financials');
        $response->assertSee('Tournament Analytics');
    }

    public function test_player_cannot_access_reports_page(): void
    {
        $response = $this->actingAs($this->player)
            ->get('/admin/reports');

        $response->assertStatus(403);
    }
}

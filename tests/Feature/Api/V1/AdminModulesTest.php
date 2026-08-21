<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Booking;
use App\Models\TournamentRegistration;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModulesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $player;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->admin->assignRole('admin');

        $this->player = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_access_payments_module(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/payments');

        $response->assertOk();
        $response->assertViewIs('content.admin.payments.index');
        $response->assertSee('Court Bookings');
        $response->assertSee('Tournament Registrations');
    }

    public function test_admin_can_access_revenue_reports(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/revenue-reports');

        $response->assertOk();
        $response->assertViewIs('content.admin.reports.revenue');
        $response->assertSee('Revenue Reports');
        $response->assertSee('Monthly Breakdown');
    }

    public function test_admin_can_access_and_update_settings(): void
    {
        // View settings
        $response = $this->actingAs($this->admin)
            ->get('/admin/settings');

        $response->assertOk();
        $response->assertViewIs('content.admin.settings.index');

        // Update settings
        $responseUpdate = $this->actingAs($this->admin)
            ->post('/admin/settings', [
                'platform_name' => 'Custom Name Test',
                'contact_email' => 'test-email@example.com',
                'currency' => 'USD',
                'commission_percentage' => 12.5,
                'service_fee' => 75.00,
            ]);

        $responseUpdate->assertRedirect('/admin/settings');
        $responseUpdate->assertSessionHas('success', 'Settings updated successfully.');

        // Re-visit and check updated fields
        $response2 = $this->actingAs($this->admin)
            ->get('/admin/settings');
        $response2->assertSee('Custom Name Test');
        $response2->assertSee('test-email@example.com');
    }

    public function test_player_cannot_access_admin_modules(): void
    {
        $this->actingAs($this->player)->get('/admin/payments')->assertStatus(403);
        $this->actingAs($this->player)->get('/admin/revenue-reports')->assertStatus(403);
        $this->actingAs($this->player)->get('/admin/settings')->assertStatus(403);
    }
}

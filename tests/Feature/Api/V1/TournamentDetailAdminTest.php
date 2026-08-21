<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentDetailAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $player;
    private Tournament $tournament;

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

        // Create club organizer
        $club = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
        ]);

        // Create tournament
        $this->tournament = Tournament::create([
            'club_id' => $club->id,
            'name' => 'Admin Test Tournament',
            'format' => 'Knockout',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'registration_deadline' => '2026-08-15T18:00:00Z',
            'tournament_type' => 'CLUB_MEMBERS_ONLY',
            'gender' => 'OPEN',
            'player_level' => ['INTERMEDIATE'],
            'age_group' => '15-45',
            'maximum_players' => 10,
            'status' => 'pending',
        ]);

        // Create registration
        TournamentRegistration::create([
            'tournament_id' => $this->tournament->id,
            'player_id' => $this->player->id,
            'payment_method_id' => 'cash',
            'payment_status' => 'paid',
            'registration_status' => 'registered',
            'amount' => 1500,
            'currency' => 'PKR',
        ]);
    }

    public function test_admin_can_view_tournament_detail_with_registrations(): void
    {
        $response = $this->actingAs($this->admin)
            ->get("/admin/tournaments/{$this->tournament->id}");

        $response->assertOk();
        $response->assertViewIs('content.admin.tournaments.show');
        $response->assertSee('Admin Test Tournament');
        $response->assertSee('Registrations & Teams', false);
        $response->assertSee($this->player->name);
    }

    public function test_admin_can_update_tournament_status_with_new_status_keys(): void
    {
        $response = $this->actingAs($this->admin)
            ->post("/admin/tournaments/{$this->tournament->id}/status", [
                'status' => 'soft_accepted',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tournaments', [
            'id' => $this->tournament->id,
            'status' => 'soft_accepted',
        ]);
    }
}

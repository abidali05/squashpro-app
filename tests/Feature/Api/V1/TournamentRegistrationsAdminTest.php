<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentRegistrationsAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $player;
    private TournamentRegistration $registration;

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
            'name' => 'Enrollment Test Player',
        ]);

        // Create club organizer
        $club = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
        ]);

        // Create tournament
        $tournament = Tournament::create([
            'club_id' => $club->id,
            'name' => 'Test Tournament Registrations',
            'format' => 'Knockout',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'registration_deadline' => '2026-08-15T18:00:00Z',
            'tournament_type' => 'CLUB_MEMBERS_ONLY',
            'gender' => 'OPEN',
            'player_level' => ['INTERMEDIATE'],
            'age_group' => '15-45',
            'maximum_players' => 10,
            'status' => 'open',
            'allowed_player' => 10,
            'registered_players_count' => 1,
        ]);

        // Create registration
        $this->registration = TournamentRegistration::create([
            'tournament_id' => $tournament->id,
            'player_id' => $this->player->id,
            'payment_method_id' => 'cash',
            'payment_status' => 'paid',
            'registration_status' => 'registered',
            'amount' => 1500,
            'currency' => 'PKR',
        ]);
    }

    public function test_admin_can_access_tournament_registrations_index_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/tournament-registrations');

        $response->assertOk();
        $response->assertViewIs('content.admin.tournaments.registrations');
        $response->assertSee('Test Tournament Registrations');
        $response->assertSee('Enrollment Test Player');
    }

    public function test_admin_can_cancel_registration_and_decrements_count(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete("/admin/tournament-registrations/{$this->registration->id}");

        $response->assertRedirect();
        $this->assertDatabaseHas('tournament_registrations', [
            'id' => $this->registration->id,
            'registration_status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('tournaments', [
            'id' => $this->registration->tournament_id,
            'registered_players_count' => 0,
        ]);
    }
}

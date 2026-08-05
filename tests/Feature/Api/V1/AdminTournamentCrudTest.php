<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Tournament;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTournamentCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $player;
    private User $club;

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

        // Create club user
        $this->club = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_access_create_tournament_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/tournaments/create');

        $response->assertOk();
        $response->assertViewIs('content.admin.tournaments.create');
        $response->assertSee($this->club->club_name);
    }

    public function test_admin_can_store_new_tournament(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/tournaments', [
                'club_id' => $this->club->id,
                'name' => 'Admin Tournament 1',
                'format' => 'knockout',
                'start_date' => '2026-08-20',
                'end_date' => '2026-08-22',
                'registration_deadline' => '2026-08-15 12:00:00',
                'entry_fees' => 1000,
                'prize_pool' => 30000,
                'maximum_players' => 16,
                'tournament_type' => 'OPEN',
                'gender' => 'OPEN',
                'player_level' => ['BEGINNER', 'INTERMEDIATE'],
                'age_group' => '18-35',
                'rules' => 'Rule 1',
            ]);

        $response->assertRedirect(route('admin.tournaments.index'));
        $this->assertDatabaseHas('tournaments', [
            'name' => 'Admin Tournament 1',
            'tournament_type' => 'OPEN',
            'allowed_player' => 16,
        ]);
    }

    public function test_admin_can_access_edit_tournament_page(): void
    {
        $tournament = Tournament::create([
            'club_id' => $this->club->id,
            'name' => 'Tournament to Edit',
            'format' => 'league',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'registration_deadline' => '2026-08-15 12:00:00',
            'entry_fees' => 1000,
            'prize_pool' => 30000,
            'maximum_players' => 16,
            'tournament_type' => 'CLUB_MEMBERS_ONLY',
            'gender' => 'MALE',
            'player_level' => ['ADVANCED'],
            'age_group' => '15-45',
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/tournaments/{$tournament->id}/edit");

        $response->assertOk();
        $response->assertViewIs('content.admin.tournaments.edit');
        $response->assertSee('Tournament to Edit');
    }

    public function test_admin_can_update_tournament(): void
    {
        $tournament = Tournament::create([
            'club_id' => $this->club->id,
            'name' => 'Tournament to Update',
            'format' => 'league',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'registration_deadline' => '2026-08-15 12:00:00',
            'entry_fees' => 1000,
            'prize_pool' => 30000,
            'maximum_players' => 16,
            'tournament_type' => 'CLUB_MEMBERS_ONLY',
            'gender' => 'MALE',
            'player_level' => ['ADVANCED'],
            'age_group' => '15-45',
        ]);

        $response = $this->actingAs($this->admin)
            ->put("/admin/tournaments/{$tournament->id}", [
                'club_id' => $this->club->id,
                'name' => 'Tournament Updated Name',
                'format' => 'league',
                'start_date' => '2026-08-21',
                'end_date' => '2026-08-23',
                'registration_deadline' => '2026-08-16 12:00:00',
                'entry_fees' => 1200,
                'prize_pool' => 35000,
                'maximum_players' => 32,
                'tournament_type' => 'CLUB_TO_CLUB',
                'opponent_club_id' => $this->club->id, // validation will fail if same, let's create another club
                'gender' => 'MALE',
                'player_level' => ['ADVANCED'],
                'age_group' => '15-45',
                'status' => 'confirmed',
            ]);

        $response->assertSessionHasErrors('opponent_club_id');

        $anotherClub = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->put("/admin/tournaments/{$tournament->id}", [
                'club_id' => $this->club->id,
                'name' => 'Tournament Updated Name',
                'format' => 'league',
                'start_date' => '2026-08-21',
                'end_date' => '2026-08-23',
                'registration_deadline' => '2026-08-16 12:00:00',
                'entry_fees' => 1200,
                'prize_pool' => 35000,
                'maximum_players' => 32,
                'tournament_type' => 'CLUB_TO_CLUB',
                'opponent_club_id' => $anotherClub->id,
                'gender' => 'MALE',
                'player_level' => ['ADVANCED'],
                'age_group' => '15-45',
                'status' => 'confirmed',
            ]);

        $response->assertRedirect(route('admin.tournaments.index'));
        $tournament->refresh();
        $this->assertEquals('Tournament Updated Name', $tournament->name);
        $this->assertEquals([$anotherClub->id], $tournament->opponent_club_id);
        $this->assertEquals('confirmed', $tournament->status);
    }

    public function test_admin_can_delete_tournament(): void
    {
        $tournament = Tournament::create([
            'club_id' => $this->club->id,
            'name' => 'Tournament to Delete',
            'format' => 'league',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'registration_deadline' => '2026-08-15 12:00:00',
            'entry_fees' => 1000,
            'prize_pool' => 30000,
            'maximum_players' => 16,
            'tournament_type' => 'CLUB_MEMBERS_ONLY',
            'gender' => 'MALE',
            'player_level' => ['ADVANCED'],
            'age_group' => '15-45',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete("/admin/tournaments/{$tournament->id}");

        $response->assertRedirect(route('admin.tournaments.index'));
        $this->assertDatabaseMissing('tournaments', [
            'id' => $tournament->id,
        ]);
    }

    public function test_non_admin_cannot_access_tournament_crud(): void
    {
        $response = $this->actingAs($this->player)
            ->get('/admin/tournaments/create');
        $response->assertForbidden();

        $response = $this->actingAs($this->player)
            ->post('/admin/tournaments', []);
        $response->assertForbidden();
    }
}

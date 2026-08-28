<?php

namespace Tests\Feature\Api\V1;

use App\Models\Court;
use App\Models\Tournament;
use App\Models\TournamentFixture;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFixtureManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

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
    }

    public function test_admin_can_access_fixtures_index_page(): void
    {
        $club = User::factory()->create(['role' => 'club', 'status' => 'active']);
        $court = Court::create([
            'club_id' => $club->id,
            'name' => 'Court A',
            'type' => 'glass',
            'price_per_hour' => 100,
            'capacity' => 2,
            'status' => 'active',
        ]);

        $tournament = Tournament::create([
            'club_id' => $club->id,
            'name' => 'Summer Open',
            'format' => 'Knockout',
            'tournament_type' => 'CLUB_TO_CLUB',
            'gender' => 'OPEN',
            'player_level' => ['BEGINNER'],
            'age_group' => '15-35',
            'maximum_players' => 10,
            'status' => 'open',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-05',
            'registration_deadline' => '2026-08-30T18:00:00Z',
        ]);

        $fixture = TournamentFixture::create([
            'tournament_id' => $tournament->id,
            'round' => 'Quarter Final',
            'home_club_id' => $club->id,
            'court_id' => $court->id,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.fixtures.index'));

        $response->assertOk()
            ->assertSee('Fixtures Management')
            ->assertSee('Quarter Final')
            ->assertSee('Court A');
    }

    public function test_admin_can_view_fixture_details(): void
    {
        $club = User::factory()->create(['role' => 'club', 'status' => 'active']);

        $tournament = Tournament::create([
            'club_id' => $club->id,
            'name' => 'Winter League',
            'format' => 'League',
            'tournament_type' => 'CLUB_TO_CLUB',
            'gender' => 'OPEN',
            'player_level' => ['BEGINNER'],
            'age_group' => '15-35',
            'maximum_players' => 10,
            'status' => 'open',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-05',
            'registration_deadline' => '2026-08-30T18:00:00Z',
        ]);

        $fixture = TournamentFixture::create([
            'tournament_id' => $tournament->id,
            'round' => 'Round 1',
            'home_club_id' => $club->id,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.fixtures.show', $fixture));

        $response->assertOk()
            ->assertSee('Fixture #' . $fixture->id . ' Details')
            ->assertSee('Winter League');
    }

    public function test_admin_can_delete_fixture(): void
    {
        $club = User::factory()->create(['role' => 'club', 'status' => 'active']);

        $tournament = Tournament::create([
            'club_id' => $club->id,
            'name' => 'Spring Cup',
            'format' => 'Knockout',
            'tournament_type' => 'CLUB_TO_CLUB',
            'gender' => 'OPEN',
            'player_level' => ['BEGINNER'],
            'age_group' => '15-35',
            'maximum_players' => 10,
            'status' => 'open',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-05',
            'registration_deadline' => '2026-08-30T18:00:00Z',
        ]);

        $fixture = TournamentFixture::create([
            'tournament_id' => $tournament->id,
            'round' => 'Semi Final',
            'home_club_id' => $club->id,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.fixtures.destroy', $fixture));

        $response->assertRedirect(route('admin.fixtures.index'));
        $this->assertDatabaseMissing('tournament_fixtures', ['id' => $fixture->id]);
    }
}

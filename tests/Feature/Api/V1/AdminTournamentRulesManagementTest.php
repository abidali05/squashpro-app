<?php

namespace Tests\Feature\Api\V1;

use App\Models\ClubTournamentRule;
use App\Models\Tournament;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTournamentRulesManagementTest extends TestCase
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

    public function test_admin_can_access_tournament_rules_index_page(): void
    {
        $club = User::factory()->create(['role' => 'club', 'status' => 'active', 'club_name' => 'Rule Host Club']);

        $tournament = Tournament::create([
            'club_id' => $club->id,
            'name' => 'Rule Test Tournament',
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

        $rule = ClubTournamentRule::create([
            'club_id' => $club->id,
            'tournament_id' => $tournament->id,
            'tournament_format' => 'league',
            'note' => 'Standard PAR-11 scoring applies.',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.tournament-rules.index'));

        $response->assertOk()
            ->assertSee('Tournament Rules')
            ->assertSee('Rule Test Tournament')
            ->assertSee('Rule Host Club');
    }

    public function test_admin_can_view_tournament_rule_details(): void
    {
        $club = User::factory()->create(['role' => 'club', 'status' => 'active']);

        $tournament = Tournament::create([
            'club_id' => $club->id,
            'name' => 'Championship Rules Test',
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

        $rule = ClubTournamentRule::create([
            'club_id' => $club->id,
            'tournament_id' => $tournament->id,
            'tournament_format' => 'knockout',
            'scoring_rules' => ['best_of' => 5, 'points' => 11],
            'note' => 'Dunlop Pro double yellow dot ball must be used.',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.tournament-rules.show', $rule));

        $response->assertOk()
            ->assertSee('Rules for Championship Rules Test')
            ->assertSee('Dunlop Pro double yellow dot ball must be used.');
    }

    public function test_admin_can_delete_tournament_rule(): void
    {
        $club = User::factory()->create(['role' => 'club', 'status' => 'active']);

        $tournament = Tournament::create([
            'club_id' => $club->id,
            'name' => 'Delete Rule Tournament',
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

        $rule = ClubTournamentRule::create([
            'club_id' => $club->id,
            'tournament_id' => $tournament->id,
            'tournament_format' => 'knockout',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.tournament-rules.destroy', $rule));

        $response->assertRedirect(route('admin.tournament-rules.index'));
        $this->assertDatabaseMissing('club_tournament_rules', ['id' => $rule->id]);
    }
}

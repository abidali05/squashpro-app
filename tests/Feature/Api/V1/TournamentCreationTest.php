<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Tournament;
use App\Models\ClubMembership;
use App\Notifications\Tournament\TournamentInvitationNotification;
use App\Notifications\Tournament\TournamentAvailableNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TournamentCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $club1;
    private User $club2;
    private string $token1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->club1 = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'club_name' => 'Club One Squash',
        ]);

        $this->club2 = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'club_name' => 'Club Two Squash',
        ]);

        // Setup API token for club1
        $plainToken = 'test-club-token-12345';
        $this->club1->api_access_token = hash('sha256', $plainToken);
        $this->club1->save();
        $this->token1 = $plainToken;
    }

    public function test_create_club_to_club_tournament_successfully(): void
    {
        Notification::fake();

        $response = $this->postJson(
            '/api/v1/club/tournaments',
            [
                'name' => 'Summer Derby',
                'format' => 'Knockout',
                'start_date' => '2026-08-20',
                'end_date' => '2026-08-22',
                'registration_deadline' => '2026-08-15T18:00:00Z',
                'entry_fees' => 150,
                'prize_pool' => 1000,
                'rules' => 'Standard rules.',
                'tournament_type' => 'CLUB_TO_CLUB',
                'opponent_club_id' => $this->club2->id,
                'gender' => 'MALE',
                'player_level' => ['INTERMEDIATE', 'ADVANCED'],
                'age_group' => '15-25',
                'maximum_players' => 10,
            ],
            [
                'Authorization' => "Bearer {$this->token1}",
            ]
        );

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure(['data' => ['id', 'status']]);

        $tournamentId = $response->json('data.id');
        $this->assertEquals('pending', $response->json('data.status'));

        // Assert database records
        $this->assertDatabaseHas('tournaments', [
            'id' => $tournamentId,
            'club_id' => $this->club1->id,
            'opponent_club_id' => $this->club2->id,
            'tournament_type' => 'CLUB_TO_CLUB',
            'status' => 'pending',
            'maximum_players' => 10,
            'allowed_player' => 10,
            'gender' => 'MALE',
            'age_group' => '15-25',
        ]);

        // Assert player_level array is saved correctly
        $tournament = Tournament::find($tournamentId);
        $this->assertEquals(['INTERMEDIATE', 'ADVANCED'], $tournament->player_level);

        // Audit log exists
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->club1->id,
            'action' => 'create_tournament_invitation',
            'entity_type' => Tournament::class,
            'entity_id' => $tournamentId,
        ]);

        // Opponent club notified
        Notification::assertSentTo(
            $this->club2,
            TournamentInvitationNotification::class,
            function ($notification, $channels) use ($tournamentId) {
                $data = $notification->toArray($this->club2);
                return $data['data']['tournament_id'] === $tournamentId;
            }
        );
    }

    public function test_create_club_members_only_tournament_successfully(): void
    {
        Notification::fake();

        // Seed 4 players
        // 1: Eligible (approved, active, male, advanced, age 20)
        $eligiblePlayer = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'gender' => 'male',
            'playing_level' => 'advanced',
            'dob' => '2006-01-01', // age 20 in 2026
        ]);
        ClubMembership::create([
            'club_id' => $this->club1->id,
            'player_id' => $eligiblePlayer->id,
            'membership_number' => 'MEM-1',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        // 2: Non-approved member
        $pendingPlayer = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'gender' => 'male',
            'playing_level' => 'advanced',
            'dob' => '2006-01-01',
        ]);
        ClubMembership::create([
            'club_id' => $this->club1->id,
            'player_id' => $pendingPlayer->id,
            'membership_number' => 'MEM-2',
            'status' => 'pending',
        ]);

        // 3: Non-matching gender
        $femalePlayer = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'gender' => 'female',
            'playing_level' => 'advanced',
            'dob' => '2006-01-01',
        ]);
        ClubMembership::create([
            'club_id' => $this->club1->id,
            'player_id' => $femalePlayer->id,
            'membership_number' => 'MEM-3',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        // 4: Non-matching level
        $beginnerPlayer = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'gender' => 'male',
            'playing_level' => 'beginner',
            'dob' => '2006-01-01',
        ]);
        ClubMembership::create([
            'club_id' => $this->club1->id,
            'player_id' => $beginnerPlayer->id,
            'membership_number' => 'MEM-4',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $response = $this->postJson(
            '/api/v1/club/tournaments',
            [
                'name' => 'Internal Derby',
                'format' => 'Round Robin',
                'start_date' => '2026-09-10',
                'end_date' => '2026-09-12',
                'registration_deadline' => '2026-09-05T18:00:00Z',
                'entry_fees' => 50,
                'prize_pool' => 500,
                'rules' => 'Round robin rules.',
                'tournament_type' => 'CLUB_MEMBERS_ONLY',
                'gender' => 'MALE',
                'player_level' => ['INTERMEDIATE', 'ADVANCED'],
                'age_group' => '15-25',
                'maximum_players' => 16,
            ],
            [
                'Authorization' => "Bearer {$this->token1}",
            ]
        );

        $response->assertCreated();
        $this->assertEquals('open', $response->json('data.status'));

        // Assert notification went to eligible player only
        Notification::assertSentTo($eligiblePlayer, TournamentAvailableNotification::class);
        Notification::assertNotSentTo($pendingPlayer, TournamentAvailableNotification::class);
        Notification::assertNotSentTo($femalePlayer, TournamentAvailableNotification::class);
        Notification::assertNotSentTo($beginnerPlayer, TournamentAvailableNotification::class);
    }

    public function test_validation_prevent_organizing_club_as_opponent(): void
    {
        $response = $this->postJson(
            '/api/v1/club/tournaments',
            [
                'name' => 'Invalid Derby',
                'format' => 'Knockout',
                'start_date' => '2026-08-20',
                'end_date' => '2026-08-22',
                'registration_deadline' => '2026-08-15T18:00:00Z',
                'entry_fees' => 150,
                'prize_pool' => 1000,
                'rules' => 'Standard rules.',
                'tournament_type' => 'CLUB_TO_CLUB',
                'opponent_club_id' => $this->club1->id, // Organizing club itself
                'gender' => 'MALE',
                'player_level' => ['ADVANCED'],
                'age_group' => '15-25',
                'maximum_players' => 10,
            ],
            [
                'Authorization' => "Bearer {$this->token1}",
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['opponent_club_id']);
    }
}

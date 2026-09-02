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
                'tournament_image' => 'https://example.com/image.jpg',
                'name' => 'Summer Derby',
                'format' => 'knockout',
                'start_date' => '2026-08-20',
                'end_date' => '2026-08-22',
                'registration_deadline' => '2026-08-15T18:00:00Z',
                'entry_fees' => 150,
                'prize_pool' => 1000,
                'rules' => 'Standard rules.',
                'tournament_type' => 'CLUB_TO_CLUB',
                'opponent_club_id' => $this->club2->id,
                'gender' => 'MALE',
                'player_level' => ['INTERMEDIATE', 'PROFESSIONAL'],
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
        $tournament = Tournament::find($tournamentId);
        $this->assertNotNull($tournament);
        $this->assertEquals($this->club1->id, $tournament->club_id);
        $this->assertEquals([$this->club2->id], $tournament->opponent_club_id);
        $this->assertEquals('CLUB_TO_CLUB', $tournament->tournament_type);
        $this->assertEquals('pending', $tournament->status);
        $this->assertEquals(10, $tournament->maximum_players);
        $this->assertEquals(10, $tournament->allowed_player);
        $this->assertEquals('MALE', $tournament->gender);
        $this->assertEquals('15-25', $tournament->age_group);

        // Assert player_level array is saved correctly
        $tournament = Tournament::find($tournamentId);
        $this->assertEquals(['INTERMEDIATE', 'PROFESSIONAL'], $tournament->player_level);

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
                'tournament_image' => 'https://example.com/image.jpg',
                'name' => 'Internal Derby',
                'format' => 'league',
                'start_date' => '2026-09-10',
                'end_date' => '2026-09-12',
                'registration_deadline' => '2026-09-05T18:00:00Z',
                'entry_fees' => 50,
                'prize_pool' => 500,
                'rules' => 'Round robin rules.',
                'tournament_type' => 'CLUB_MEMBERS_ONLY',
                'gender' => 'MALE',
                'player_level' => ['INTERMEDIATE', 'PROFESSIONAL'],
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
                'player_level' => ['INTERMEDIATE'],
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

    public function test_multi_club_and_host_team_creation_flow(): void
    {
        Notification::fake();

        // Create club3
        $club3 = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'club_name' => 'Club Three Squash',
        ]);

        // Create players for host club (club1)
        $player1 = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'gender' => 'MALE',
            'playing_level' => 'INTERMEDIATE',
            'dob' => '2005-05-15', // Age 21
        ]);
        $player2 = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'gender' => 'MALE',
            'playing_level' => 'INTERMEDIATE',
            'dob' => '2004-03-10', // Age 22
        ]);
        
        // Add them as approved members to club1
        ClubMembership::create([
            'club_id' => $this->club1->id,
            'player_id' => $player1->id,
            'membership_number' => 'CM1-001',
            'status' => ClubMembership::STATUS_APPROVED,
        ]);
        ClubMembership::create([
            'club_id' => $this->club1->id,
            'player_id' => $player2->id,
            'membership_number' => 'CM1-002',
            'status' => ClubMembership::STATUS_APPROVED,
        ]);

        // Create a player not belonging to club1 (or ineligible)
        $ineligiblePlayer = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'gender' => 'FEMALE', // Wrong gender
            'playing_level' => 'BEGINNER', // Wrong level
            'dob' => '2005-05-15',
        ]);

        $payload = [
            'tournament_image' => 'https://example.com/image.jpg',
            'name' => 'Winter League Clash',
            'format' => 'knockout',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'registration_deadline' => '2026-08-15T18:00:00Z',
            'entry_fees' => 200,
            'prize_pool' => 1500,
            'rules' => 'Championship rules.',
            'tournament_type' => 'CLUB_TO_CLUB',
            'invited_club_ids' => [$this->club2->id, $club3->id],
            'host_team_player_ids' => [$player2->id, $player1->id], // Sequenced
            'gender' => 'MALE',
            'player_level' => ['INTERMEDIATE'],
            'age_group' => '15-25',
            'maximum_players' => 10,
        ];

        // 1. Success Flow
        $response = $this->postJson(
            '/api/v1/club/tournaments',
            $payload,
            ['Authorization' => "Bearer {$this->token1}"]
        );

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $tournamentId = $response->json('data.id');

        // Verify invitations created in database
        $this->assertDatabaseHas('tournament_invitations', [
            'tournament_id' => $tournamentId,
            'invited_club_id' => $this->club2->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('tournament_invitations', [
            'tournament_id' => $tournamentId,
            'invited_club_id' => $club3->id,
            'status' => 'pending',
        ]);

        // Verify host team and player sequence in database
        $this->assertDatabaseHas('tournament_teams', [
            'tournament_id' => $tournamentId,
            'club_id' => $this->club1->id,
            'submission_status' => 'submitted',
        ]);
        
        $team = \App\Models\TournamentTeam::where('tournament_id', $tournamentId)
            ->where('club_id', $this->club1->id)
            ->first();

        $this->assertDatabaseHas('tournament_team_players', [
            'team_id' => $team->id,
            'player_id' => $player2->id,
            'position' => 1, // player2 was first in list
        ]);
        $this->assertDatabaseHas('tournament_team_players', [
            'team_id' => $team->id,
            'player_id' => $player1->id,
            'position' => 2, // player1 was second
        ]);

        // 2. Validation: host club included in invited_club_ids
        $payloadInvalid1 = $payload;
        $payloadInvalid1['invited_club_ids'] = [$this->club2->id, $this->club1->id]; // organizing club included

        $responseInvalid1 = $this->postJson(
            '/api/v1/club/tournaments',
            $payloadInvalid1,
            ['Authorization' => "Bearer {$this->token1}"]
        );
        $responseInvalid1->assertStatus(422)
            ->assertJsonValidationErrors(['invited_club_ids']);

        // 3. Validation: ineligible player in host team
        $payloadInvalid2 = $payload;
        $payloadInvalid2['host_team_player_ids'] = [$player2->id, $ineligiblePlayer->id];

        $responseInvalid2 = $this->postJson(
            '/api/v1/club/tournaments',
            $payloadInvalid2,
            ['Authorization' => "Bearer {$this->token1}"]
        );
        $responseInvalid2->assertStatus(422)
            ->assertJsonPath('error_code', 'PLAYER_NOT_ELIGIBLE');

        // 4. Validation: duplicate player IDs inside host team
        $payloadInvalid3 = $payload;
        $payloadInvalid3['host_team_player_ids'] = [$player1->id, $player1->id];

        $responseInvalid3 = $this->postJson(
            '/api/v1/club/tournaments',
            $payloadInvalid3,
            ['Authorization' => "Bearer {$this->token1}"]
        );
        $responseInvalid3->assertStatus(422)
            ->assertJsonValidationErrors(['host_team_player_ids.1']);
    }

    public function test_tournament_creation_with_gender_all_allows_both_male_and_female_players(): void
    {
        $malePlayer = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'gender' => 'male',
            'playing_level' => 'intermediate',
            'dob' => '2000-01-01',
        ]);
        ClubMembership::create([
            'club_id' => $this->club1->id,
            'player_id' => $malePlayer->id,
            'membership_number' => 'CM-MALE',
            'status' => ClubMembership::STATUS_APPROVED,
        ]);

        $femalePlayer = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'gender' => 'female',
            'playing_level' => 'intermediate',
            'dob' => '2000-01-01',
        ]);
        ClubMembership::create([
            'club_id' => $this->club1->id,
            'player_id' => $femalePlayer->id,
            'membership_number' => 'CM-FEMALE',
            'status' => ClubMembership::STATUS_APPROVED,
        ]);

        $payload = [
            'tournament_image' => 'https://example.com/tournament.jpg',
            'name' => 'Mixed Open Cup',
            'format' => 'knockout',
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-15',
            'registration_deadline' => '2026-10-05',
            'entry_fees' => 100,
            'prize_pool' => 1000,
            'tournament_type' => 'CLUB_TO_CLUB',
            'invited_club_ids' => [$this->club2->id],
            'host_team_player_ids' => [$malePlayer->id, $femalePlayer->id],
            'gender' => 'ALL',
            'player_level' => ['INTERMEDIATE'],
            'age_group' => '18-40',
            'maximum_players' => 16,
        ];

        $response = $this->postJson(
            '/api/v1/club/tournaments',
            $payload,
            ['Authorization' => "Bearer {$this->token1}"]
        );

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $tournament = \App\Models\Tournament::find($response->json('data.id'));
        $this->assertEquals('ALL', $tournament->gender);
    }
}

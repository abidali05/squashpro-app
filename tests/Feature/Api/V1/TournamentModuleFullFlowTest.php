<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\ClubMembership;
use App\Notifications\Tournament\TournamentInvitationAcceptedNotification;
use App\Notifications\Tournament\TournamentInvitationRejectedNotification;
use App\Notifications\Tournament\TournamentTeamSubmittedNotification;
use App\Notifications\Tournament\PlayerParticipationNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TournamentModuleFullFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $club1;
    private User $club2;
    private User $club3;
    private string $token1;
    private string $token2;
    private string $token3;

    private User $player1;
    private User $player2;
    private User $player3;
    private string $playerToken1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        // Create 3 active clubs
        $this->club1 = User::factory()->create(['role' => 'club', 'status' => 'active', 'club_name' => 'Club Alpha']);
        $this->club2 = User::factory()->create(['role' => 'club', 'status' => 'active', 'club_name' => 'Club Beta']);
        $this->club3 = User::factory()->create(['role' => 'club', 'status' => 'active', 'club_name' => 'Club Gamma']);

        // Setup tokens for clubs
        $this->token1 = $this->setupTokenFor($this->club1, 'token-1');
        $this->token2 = $this->setupTokenFor($this->club2, 'token-2');
        $this->token3 = $this->setupTokenFor($this->club3, 'token-3');

        // Create 3 players
        // player1: Male, Intermediate, age 20 (dob 2006-01-01)
        $this->player1 = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'gender' => 'male',
            'playing_level' => 'intermediate',
            'dob' => '2006-01-01',
            'name' => 'Player One',
        ]);
        $this->playerToken1 = $this->setupTokenFor($this->player1, 'player-token-1');

        // player2: Female, Advanced, age 22 (dob 2004-01-01)
        $this->player2 = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'gender' => 'female',
            'playing_level' => 'advanced',
            'dob' => '2004-01-01',
            'name' => 'Player Two',
        ]);

        // player3: Male, Advanced, age 40 (dob 1986-01-01)
        $this->player3 = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'gender' => 'male',
            'playing_level' => 'advanced',
            'dob' => '1986-01-01',
            'name' => 'Player Three',
        ]);

        // Add approved memberships
        ClubMembership::create([
            'club_id' => $this->club2->id,
            'player_id' => $this->player1->id,
            'membership_number' => 'CSC-B01',
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        ClubMembership::create([
            'club_id' => $this->club2->id,
            'player_id' => $this->player2->id,
            'membership_number' => 'CSC-B02',
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    private function setupTokenFor(User $user, string $token): string
    {
        $user->api_access_token = hash('sha256', $token);
        $user->save();
        return $token;
    }

    public function test_respond_to_invitation_accept_and_reject_flows(): void
    {
        Notification::fake();

        // 1. Create a tournament from club1 inviting club2
        $tournament = Tournament::create([
            'club_id' => $this->club1->id,
            'opponent_club_id' => $this->club2->id,
            'name' => 'Inter-Club Cup',
            'format' => 'Knockout',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'registration_deadline' => '2026-08-15T18:00:00Z',
            'tournament_type' => 'CLUB_TO_CLUB',
            'gender' => 'OPEN',
            'player_level' => ['INTERMEDIATE', 'ADVANCED'],
            'age_group' => '15-45',
            'maximum_players' => 2,
            'status' => 'pending',
        ]);

        // 2. Reject by unauthorized club (club3) -> 403
        $responseForbidden = $this->patchJson(
            "/api/v1/club/tournaments/{$tournament->id}/invitation",
            ['decision' => 'ACCEPT'],
            ['Authorization' => "Bearer {$this->token3}"]
        );
        $responseForbidden->assertStatus(403);

        // 3. Accept by invited club (club2) -> 200
        $responseAccept = $this->patchJson(
            "/api/v1/club/tournaments/{$tournament->id}/invitation",
            ['decision' => 'ACCEPT'],
            ['Authorization' => "Bearer {$this->token2}"]
        );
        $responseAccept->assertOk();
        $responseAccept->assertJsonPath('data.status', 'soft_accepted');

        // Check DB status and Audit log
        $this->assertDatabaseHas('tournaments', [
            'id' => $tournament->id,
            'status' => 'soft_accepted',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->club2->id,
            'action' => 'accept_tournament_invitation',
            'entity_id' => $tournament->id,
        ]);

        // Check organizer notified
        Notification::assertSentTo($this->club1, TournamentInvitationAcceptedNotification::class);

        // 4. Respond again to already accepted invitation -> 409
        $responseDuplicate = $this->patchJson(
            "/api/v1/club/tournaments/{$tournament->id}/invitation",
            ['decision' => 'REJECT'],
            ['Authorization' => "Bearer {$this->token2}"]
        );
        $responseDuplicate->assertStatus(409);
        $responseDuplicate->assertJsonPath('error_code', 'ALREADY_RESPONDED');
    }

    public function test_eligible_players_endpoint_filters_correctly(): void
    {
        // Create tournament inviting club2
        // Criteria: MALE, INTERMEDIATE, age 15-25
        $tournament = Tournament::create([
            'club_id' => $this->club1->id,
            'opponent_club_id' => $this->club2->id,
            'name' => 'Intermediate Derby',
            'format' => 'Knockout',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'registration_deadline' => '2026-08-15T18:00:00Z',
            'tournament_type' => 'CLUB_TO_CLUB',
            'gender' => 'MALE',
            'player_level' => ['INTERMEDIATE'],
            'age_group' => '15-25',
            'maximum_players' => 2,
            'status' => 'soft_accepted',
        ]);

        // Request eligible players as club2
        $response = $this->getJson(
            "/api/v1/club/tournaments/{$tournament->id}/eligible-players",
            ['Authorization' => "Bearer {$this->token2}"]
        );

        $response->assertOk();
        // player1 is eligible (Male, Intermediate, age 20)
        // player2 is NOT eligible (Female, Advanced)
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.player_id', $this->player1->id);
        $response->assertJsonPath('data.0.membership_number', 'CSC-B01');
    }

    public function test_submit_team_roster_endpoint(): void
    {
        Notification::fake();

        // Tournament setup
        $tournament = Tournament::create([
            'club_id' => $this->club1->id,
            'opponent_club_id' => $this->club2->id,
            'name' => 'Team Roster Cup',
            'format' => 'Knockout',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'registration_deadline' => '2026-08-15T18:00:00Z',
            'tournament_type' => 'CLUB_TO_CLUB',
            'gender' => 'OPEN',
            'player_level' => ['INTERMEDIATE', 'ADVANCED'],
            'age_group' => '15-45',
            'maximum_players' => 2,
            'status' => 'soft_accepted',
        ]);

        // Submit players (player1 and player2) as club2
        $response = $this->postJson(
            "/api/v1/club/tournaments/{$tournament->id}/team",
            ['player_ids' => [$this->player1->id, $this->player2->id]],
            ['Authorization' => "Bearer {$this->token2}"]
        );

        $response->assertOk();
        $response->assertJsonPath('data.status', 'confirmed');

        // Check registrations table
        $this->assertDatabaseHas('tournament_registrations', [
            'tournament_id' => $tournament->id,
            'player_id' => $this->player1->id,
            'registration_status' => 'registered',
        ]);
        $this->assertDatabaseHas('tournament_registrations', [
            'tournament_id' => $tournament->id,
            'player_id' => $this->player2->id,
            'registration_status' => 'registered',
        ]);

        // Check tournament status
        $this->assertDatabaseHas('tournaments', [
            'id' => $tournament->id,
            'status' => 'confirmed',
            'registered_players_count' => 2,
        ]);

        // Audit log exists
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->club2->id,
            'action' => 'submit_tournament_team',
        ]);

        // Organizer notified
        Notification::assertSentTo($this->club1, TournamentTeamSubmittedNotification::class);
    }

    public function test_player_participation_decision_flow(): void
    {
        Notification::fake();

        // 1. Create members-only tournament at club2 (player1 is approved member there)
        // Criteria: MALE, INTERMEDIATE, age 15-25
        $tournament = Tournament::create([
            'club_id' => $this->club2->id,
            'name' => 'Internal Club Trophy',
            'format' => 'Round Robin',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-12',
            'registration_deadline' => '2026-09-05T18:00:00Z',
            'tournament_type' => 'CLUB_MEMBERS_ONLY',
            'gender' => 'MALE',
            'player_level' => ['INTERMEDIATE'],
            'age_group' => '15-25',
            'maximum_players' => 1,
            'status' => 'open',
        ]);

        // 2. Accept participation by eligible player1 -> 200
        $responseAccept = $this->patchJson(
            "/api/v1/player/tournaments/{$tournament->id}/participation",
            ['decision' => 'ACCEPT'],
            ['Authorization' => "Bearer {$this->playerToken1}"]
        );

        $responseAccept->assertOk();
        $responseAccept->assertJsonPath('data.status', 'registered');

        // Check DB
        $this->assertDatabaseHas('tournament_registrations', [
            'tournament_id' => $tournament->id,
            'player_id' => $this->player1->id,
            'registration_status' => 'registered',
        ]);
        $this->assertDatabaseHas('tournaments', [
            'id' => $tournament->id,
            'status' => 'full', // Count was 1, maximum_players is 1, so status becomes full!
            'registered_players_count' => 1,
        ]);

        // Audit exists
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->player1->id,
            'action' => 'accept_tournament_participation',
        ]);

        // Player notified
        Notification::assertSentTo($this->player1, PlayerParticipationNotification::class);

        // 3. Reject participation -> cancels registration and decrements count
        $responseReject = $this->patchJson(
            "/api/v1/player/tournaments/{$tournament->id}/participation",
            ['decision' => 'REJECT'],
            ['Authorization' => "Bearer {$this->playerToken1}"]
        );

        $responseReject->assertOk();
        $responseReject->assertJsonPath('data.status', 'cancelled');

        // Assert cancelled in DB and count decremented
        $this->assertDatabaseHas('tournament_registrations', [
            'tournament_id' => $tournament->id,
            'player_id' => $this->player1->id,
            'registration_status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('tournaments', [
            'id' => $tournament->id,
            'status' => 'open', // Status reverted to open
            'registered_players_count' => 0,
        ]);
    }

    public function test_tournament_visibility_rules(): void
    {
        // 1. Create a CLUB_TO_CLUB tournament between club1 and club2
        $c2cTournament = Tournament::create([
            'club_id' => $this->club1->id,
            'opponent_club_id' => $this->club2->id,
            'name' => 'Cup Beta Alpha',
            'format' => 'Knockout',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'registration_deadline' => '2026-08-15T18:00:00Z',
            'tournament_type' => 'CLUB_TO_CLUB',
            'gender' => 'OPEN',
            'player_level' => ['INTERMEDIATE', 'ADVANCED'],
            'age_group' => '15-45',
            'maximum_players' => 10,
            'status' => 'open',
        ]);

        // 2. Create a members-only tournament at club3 (player1 has NO membership at club3)
        $membersOnlyClub3 = Tournament::create([
            'club_id' => $this->club3->id,
            'name' => 'Internal Club Gamma Cup',
            'format' => 'Knockout',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'registration_deadline' => '2026-08-15T18:00:00Z',
            'tournament_type' => 'CLUB_MEMBERS_ONLY',
            'gender' => 'OPEN',
            'player_level' => ['INTERMEDIATE', 'ADVANCED'],
            'age_group' => '15-45',
            'maximum_players' => 10,
            'status' => 'open',
        ]);

        // 3. Create a members-only tournament at club2 (player1 is approved member there, matches criteria)
        $membersOnlyClub2 = Tournament::create([
            'club_id' => $this->club2->id,
            'name' => 'Internal Club Beta Cup',
            'format' => 'Knockout',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'registration_deadline' => '2026-08-15T18:00:00Z',
            'tournament_type' => 'CLUB_MEMBERS_ONLY',
            'gender' => 'MALE',
            'player_level' => ['INTERMEDIATE'],
            'age_group' => '15-25',
            'maximum_players' => 10,
            'status' => 'open',
        ]);

        // 4. Create a members-only tournament at club2 where player1 is a member but does NOT match criteria (gender Female)
        $membersOnlyClub2NonMatching = Tournament::create([
            'club_id' => $this->club2->id,
            'name' => 'Internal Club Beta Female Cup',
            'format' => 'Knockout',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'registration_deadline' => '2026-08-15T18:00:00Z',
            'tournament_type' => 'CLUB_MEMBERS_ONLY',
            'gender' => 'FEMALE',
            'player_level' => ['INTERMEDIATE'],
            'age_group' => '15-25',
            'maximum_players' => 10,
            'status' => 'open',
        ]);

        // Request visibility as player1 (member of club2)
        $response = $this->getJson(
            '/api/v1/player/tournaments',
            ['Authorization' => "Bearer {$this->playerToken1}"]
        );

        $response->assertOk();
        // player1 should see:
        // - c2cTournament (because they are member of club2 which is invited opponent club)
        // - membersOnlyClub2 (because they are member of club2 and match criteria)
        // player1 should NOT see:
        // - membersOnlyClub3 (because they are not a member of club3)
        // - membersOnlyClub2NonMatching (because gender criteria does not match)
        
        $ids = collect($response->json('data'))->pluck('tournament_id')->toArray();
        $this->assertContains($c2cTournament->id, $ids);
        $this->assertContains($membersOnlyClub2->id, $ids);
        $this->assertNotContains($membersOnlyClub3->id, $ids);
        $this->assertNotContains($membersOnlyClub2NonMatching->id, $ids);
    }

    public function test_club_can_list_both_organized_and_invited_tournaments(): void
    {
        // 1. Create a tournament organized by club1, inviting club2
        $tournament1 = Tournament::create([
            'club_id' => $this->club1->id,
            'opponent_club_id' => $this->club2->id,
            'name' => 'Organized Tournament',
            'format' => 'Knockout',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'registration_deadline' => '2026-08-15T18:00:00Z',
            'tournament_type' => 'CLUB_TO_CLUB',
            'gender' => 'OPEN',
            'player_level' => ['INTERMEDIATE'],
            'age_group' => '15-45',
            'maximum_players' => 10,
            'status' => 'pending',
        ]);

        // 2. Create another tournament organized by club3, inviting club1
        $tournament2 = Tournament::create([
            'club_id' => $this->club3->id,
            'opponent_club_id' => $this->club1->id,
            'name' => 'Invited Tournament',
            'format' => 'Knockout',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'registration_deadline' => '2026-08-15T18:00:00Z',
            'tournament_type' => 'CLUB_TO_CLUB',
            'gender' => 'OPEN',
            'player_level' => ['INTERMEDIATE'],
            'age_group' => '15-45',
            'maximum_players' => 10,
            'status' => 'pending',
        ]);

        // 3. List tournaments as club1
        $response = $this->getJson(
            '/api/v1/club/tournaments',
            ['Authorization' => "Bearer {$this->token1}"]
        );

        $response->assertOk();
        $tournamentsList = $response->json('data.tournaments');
        $ids = collect($tournamentsList)->pluck('id')->toArray();

        // club1 should see both
        $this->assertContains($tournament1->id, $ids);
        $this->assertContains($tournament2->id, $ids);
    }
}

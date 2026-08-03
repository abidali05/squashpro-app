<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\ClubWorkingHour;
use App\Models\ClubNonMemberWindow;
use App\Models\ClubMembership;
use App\Models\Tournament;
use App\Models\Court;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerTournamentOfficialChangesTest extends TestCase
{
    use RefreshDatabase;

    private User $club;
    private User $player;
    private string $clubToken;
    private string $playerToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        // Setup Club
        $this->club = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'club_name' => 'Model Squash Academy',
            'non_member_booking_allowed' => true,
        ]);
        $this->club->assignRole('club');
        $plainClubToken = 'test-club-token-9900';
        $this->club->api_access_token = hash('sha256', $plainClubToken);
        $this->club->save();
        $this->clubToken = $plainClubToken;

        // Setup Player
        $this->player = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'name' => 'Steve Smith',
            'gender' => 'male',
            'playing_level' => 'advanced',
            'dob' => '1995-05-15',
        ]);
        $this->player->assignRole('player');
        $plainPlayerToken = 'test-player-token-9901';
        $this->player->api_access_token = hash('sha256', $plainPlayerToken);
        $this->player->save();
        $this->playerToken = $plainPlayerToken;

        // Seed ClubWorkingHours
        ClubWorkingHour::create([
            'club_id' => $this->club->id,
            'day' => 'monday',
            'is_open' => true,
            'opens_at' => '08:00',
            'closes_at' => '22:00',
        ]);
        for ($i = 1; $i < 7; $i++) {
            $days = ['tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            ClubWorkingHour::create([
                'club_id' => $this->club->id,
                'day' => $days[$i - 1],
                'is_open' => true,
                'opens_at' => '08:00',
                'closes_at' => '22:00',
            ]);
        }

        // Seed ClubNonMemberWindow
        ClubNonMemberWindow::create([
            'club_id' => $this->club->id,
            'day' => 'monday',
            'is_available' => true,
            'from_time' => '09:00',
            'to_time' => '17:00',
        ]);
    }

    public function test_player_get_club_details_returns_day_wise_non_member_schedule(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->playerToken)
            ->getJson("/api/v1/player/clubs/{$this->club->id}");

        $response->assertOk();
        $detail = $response->json('data');

        $this->assertTrue($detail['allow_non_member_booking']);
        $this->assertIsArray($detail['non_member_booking_schedule']);
        $mondayWindow = collect($detail['non_member_booking_schedule'])->firstWhere('day', 'monday');
        $this->assertNotNull($mondayWindow);
        $this->assertTrue($mondayWindow['is_available']);
        $this->assertEquals('09:00', $mondayWindow['from_time']);
        $this->assertEquals('17:00', $mondayWindow['to_time']);
    }

    public function test_player_get_club_courts_returns_date_non_member_booking_details(): void
    {
        // Add a court
        $court = Court::create([
            'club_id' => $this->club->id,
            'name' => 'Court Elite',
            'status' => 'active',
            'price_per_hour' => 1500,
        ]);

        // Monday input (Monday is 2026-08-03)
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->playerToken)
            ->getJson("/api/v1/player/clubs/{$this->club->id}/courts?date=2026-08-03");

        $response->assertOk();
        $response->assertJsonPath('club_non_member_booking.allow_non_member_booking', true);
        $response->assertJsonPath('club_non_member_booking.day', 'monday');
        $response->assertJsonPath('club_non_member_booking.is_available', true);
        $response->assertJsonPath('club_non_member_booking.from_time', '09:00');
        $response->assertJsonPath('club_non_member_booking.to_time', '17:00');

        $courtsList = $response->json('data');
        $this->assertCount(1, $courtsList);
        $this->assertTrue($courtsList[0]['allow_non_member_booking']);
        $this->assertEquals('09:00', $courtsList[0]['non_member_booking_start_time']);
        $this->assertEquals('17:00', $courtsList[0]['non_member_booking_end_time']);
    }

    public function test_club_members_endpoint_supports_optional_eligibility_filters(): void
    {
        // Create matching/non-matching club members
        $player1 = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'gender' => 'female',
            'playing_level' => 'professional',
            'dob' => '1998-02-12', // 28 years old
        ]);
        $player2 = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'gender' => 'male',
            'playing_level' => 'advanced', // should map to professional
            'dob' => '2010-02-12', // 16 years old
        ]);

        ClubMembership::create([
            'club_id' => $this->club->id,
            'player_id' => $player1->id,
            'status' => 'approved',
            'membership_number' => 'MEM-110',
            'verification_mode' => 'manual',
        ]);
        ClubMembership::create([
            'club_id' => $this->club->id,
            'player_id' => $player2->id,
            'status' => 'approved',
            'membership_number' => 'MEM-111',
            'verification_mode' => 'manual',
        ]);

        // Filter by gender = Female
        $responseGender = $this->withHeader('Authorization', 'Bearer ' . $this->clubToken)
            ->getJson('/api/v1/club/members?gender=Female');
        $responseGender->assertOk();
        $this->assertCount(1, $responseGender->json('data'));
        $this->assertEquals('MEM-110', $responseGender->json('data.0.membership_number'));

        // Filter by player_level = Professional (matches advanced too)
        $responseLevel = $this->withHeader('Authorization', 'Bearer ' . $this->clubToken)
            ->getJson('/api/v1/club/members?player_level=Professional');
        $responseLevel->assertOk();
        $this->assertCount(2, $responseLevel->json('data'));

        // Filter by group = "15-20" age group (matches player 2)
        $responseAge = $this->withHeader('Authorization', 'Bearer ' . $this->clubToken)
            ->getJson('/api/v1/club/members?group=15-20');
        $responseAge->assertOk();
        $this->assertCount(1, $responseAge->json('data'));
        $this->assertEquals('MEM-111', $responseAge->json('data.0.membership_number'));

        // Replaced advanced term with professional in listing JSON
        $this->assertEquals('professional', $responseAge->json('data.0.player.playing_level'));
    }

    public function test_create_club_to_club_tournament_persists_scorers_and_umpires(): void
    {
        $opponent = User::factory()->create(['role' => 'club', 'status' => 'active']);
        $scorer1 = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'are_you_scorer' => true,
            'playing_level' => 'advanced',
            'gender' => 'male',
            'dob' => '1995-05-15',
        ]);
        $umpire1 = User::factory()->create(['role' => 'player', 'status' => 'active', 'are_you_umpire' => true]);

        ClubMembership::create([
            'club_id' => $this->club->id,
            'player_id' => $scorer1->id,
            'status' => 'approved',
            'membership_number' => 'MEM-SC-1',
            'verification_mode' => 'manual',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->clubToken)
            ->postJson('/api/v1/club/tournaments', [
                'name' => 'Super Cup 2026',
                'format' => 'single_elimination',
                'start_date' => now()->addDays(5)->toDateString(),
                'registration_deadline' => now()->addDays(4)->toDateString(),
                'end_date' => now()->addDays(7)->toDateString(),
                'entry_fees' => 500,
                'prize_pool' => 5000,
                'tournament_type' => 'CLUB_TO_CLUB',
                'gender' => 'OPEN',
                'player_level' => ['Professional'],
                'age_group' => '18-40',
                'maximum_players' => 16,
                'invited_club_ids' => [$opponent->id],
                'host_team_player_ids' => [$scorer1->id],
                'scorer_ids' => [$scorer1->id],
                'umpire_ids' => [$umpire1->id],
            ]);

        $response->assertStatus(201);
        $tournamentId = $response->json('data.id');

        $this->assertDatabaseHas('tournament_scorers', [
            'tournament_id' => $tournamentId,
            'user_id' => $scorer1->id,
        ]);
        $this->assertDatabaseHas('tournament_umpires', [
            'tournament_id' => $tournamentId,
            'user_id' => $umpire1->id,
        ]);

        // Assert they are returned in club tournament detail
        $responseClubDetail = $this->withHeader('Authorization', 'Bearer ' . $this->clubToken)
            ->getJson("/api/v1/club/tournaments/{$tournamentId}");

        $responseClubDetail->assertOk();
        $this->assertCount(1, $responseClubDetail->json('data.scorers'));
        $this->assertEquals($scorer1->name, $responseClubDetail->json('data.scorers.0.full_name'));
        $this->assertCount(1, $responseClubDetail->json('data.umpires'));
        $this->assertEquals($umpire1->name, $responseClubDetail->json('data.umpires.0.full_name'));

        // Assert they are returned in player tournament detail
        $responsePlayerDetail = $this->withHeader('Authorization', 'Bearer ' . $this->playerToken)
            ->getJson("/api/v1/player/tournament/{$tournamentId}");

        $responsePlayerDetail->assertOk();
        $this->assertCount(1, $responsePlayerDetail->json('data.scorers'));
        $this->assertEquals($scorer1->name, $responsePlayerDetail->json('data.scorers.0.full_name'));
        $this->assertCount(1, $responsePlayerDetail->json('data.umpires'));
        $this->assertEquals($umpire1->name, $responsePlayerDetail->json('data.umpires.0.full_name'));
    }

    public function test_club_details_update_endpoint_supports_updating_non_member_booking_schedules(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->clubToken)
            ->postJson('/api/v1/club/details/update', [
                'name' => 'Lahore Squash Hub',
                'address' => 'Ghalib Rd, Gulberg III',
                'facilities' => ['AC', 'Gym'],
                'allow_non_member_booking' => true,
                'non_member_booking_schedule' => [
                    [
                        'day' => 'monday',
                        'is_available' => true,
                        'time_ranges' => [
                            ['from' => '10:00', 'to' => '16:00'],
                        ]
                    ],
                    ['day' => 'tuesday', 'is_available' => false, 'time_ranges' => []],
                    ['day' => 'wednesday', 'is_available' => false, 'time_ranges' => []],
                    ['day' => 'thursday', 'is_available' => false, 'time_ranges' => []],
                    ['day' => 'friday', 'is_available' => false, 'time_ranges' => []],
                    ['day' => 'saturday', 'is_available' => false, 'time_ranges' => []],
                    ['day' => 'sunday', 'is_available' => false, 'time_ranges' => []],
                ]
            ]);

        $response->assertOk();
        
        $this->assertDatabaseHas('club_non_member_windows', [
            'club_id' => $this->club->id,
            'day' => 'monday',
            'is_available' => 1,
            'from_time' => '10:00',
            'to_time' => '16:00',
        ]);
        $this->assertDatabaseHas('club_non_member_windows', [
            'club_id' => $this->club->id,
            'day' => 'tuesday',
            'is_available' => 0,
        ]);
    }

    public function test_get_club_officials_returns_scorers_and_umpires_who_are_members(): void
    {
        $scorerPlayer = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'are_you_scorer' => true,
            'are_you_umpire' => false,
            'name' => 'Scorer Member',
        ]);
        $umpirePlayer = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'are_you_scorer' => false,
            'are_you_umpire' => true,
            'name' => 'Umpire Member',
        ]);
        $nonMemberScorer = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'are_you_scorer' => true,
            'are_you_umpire' => false,
            'name' => 'Scorer NonMember',
        ]);

        // Scorer member
        ClubMembership::create([
            'club_id' => $this->club->id,
            'player_id' => $scorerPlayer->id,
            'status' => 'approved',
            'membership_number' => 'MEM-SC-100',
            'verification_mode' => 'manual',
        ]);

        // Umpire member
        ClubMembership::create([
            'club_id' => $this->club->id,
            'player_id' => $umpirePlayer->id,
            'status' => 'approved',
            'membership_number' => 'MEM-UM-100',
            'verification_mode' => 'manual',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->clubToken)
            ->getJson('/api/v1/club/officials');

        $response->assertOk();
        
        $data = $response->json('data');
        $this->assertCount(1, $data['scorers']);
        $this->assertEquals('Scorer Member', $data['scorers'][0]['full_name']);

        $this->assertCount(1, $data['umpires']);
        $this->assertEquals('Umpire Member', $data['umpires'][0]['full_name']);
    }
}

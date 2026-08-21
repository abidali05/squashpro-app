<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Court;
use App\Models\CourtTimeSlot;
use App\Models\ClubMembership;
use App\Models\ClubMembershipRequest;
use App\Support\ApiErrorCode;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberBookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $club1;
    private User $club2;
    private User $player1;
    private string $playerToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        // Club 1: Non-member booking disabled
        $this->club1 = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'club_name' => 'Club Member Only',
            'non_member_booking_allowed' => false,
            'non_member_booking_start_time' => null,
            'non_member_booking_end_time' => null,
            'working_hours' => '08:00 - 22:00',
        ]);

        // Club 2: Non-member booking allowed between 09:00 and 17:00
        $this->club2 = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'club_name' => 'Club Public Allowed',
            'non_member_booking_allowed' => true,
            'non_member_booking_start_time' => '09:00:00',
            'non_member_booking_end_time' => '17:00:00',
            'working_hours' => '08:00 - 22:00',
        ]);

        // Setup player1
        $this->player1 = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'gender' => 'male',
            'playing_level' => 'intermediate',
            'dob' => '2000-01-01',
        ]);
        $plainPlayerToken = 'test-player-token-1234';
        $this->player1->api_access_token = hash('sha256', $plainPlayerToken);
        $this->player1->save();
        $this->playerToken = $plainPlayerToken;

        // Establish approved membership for player1 in Club 1
        ClubMembership::create([
            'club_id' => $this->club1->id,
            'player_id' => $this->player1->id,
            'membership_number' => 'MEM-ALPHA-001',
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function test_club_attributes_and_metadata_serialization(): void
    {
        // 1. Test GET player/dashboard
        $response = $this->getJson(
            '/api/v1/player/dashboard',
            ['Authorization' => "Bearer {$this->playerToken}"]
        );
        $response->assertOk();
        $clubs = $response->json('data.clubs');
        
        $c1Data = collect($clubs)->firstWhere('id', $this->club1->id);
        $c2Data = collect($clubs)->firstWhere('id', $this->club2->id);

        $this->assertNotNull($c1Data);
        $this->assertTrue($c1Data['is_member']);
        $this->assertEquals('approved', $c1Data['membership_status']);
        $this->assertEquals('MEM-ALPHA-001', $c1Data['membership_number']);
        $this->assertTrue($c1Data['can_book']);
        $this->assertFalse($c1Data['requires_payment']);

        $this->assertNotNull($c2Data);
        $this->assertFalse($c2Data['is_member']);
        $this->assertNull($c2Data['membership_status']);
        $this->assertTrue($c2Data['can_book']);
        $this->assertTrue($c2Data['requires_payment']);

        // 2. Test GET player/clubs
        $responseClubs = $this->getJson(
            '/api/v1/player/clubs',
            ['Authorization' => "Bearer {$this->playerToken}"]
        );
        $responseClubs->assertOk();
        $clubsList = $responseClubs->json('data');

        $c1List = collect($clubsList)->firstWhere('id', $this->club1->id);
        $c2List = collect($clubsList)->firstWhere('id', $this->club2->id);

        $this->assertFalse($c1List['allow_non_member_booking']);
        $this->assertTrue($c1List['is_member']);
        $this->assertEquals('approved', $c1List['membership_status']);
        $this->assertEquals('MEM-ALPHA-001', $c1List['membership_number']);
        $this->assertTrue($c1List['can_book']);
        $this->assertFalse($c1List['requires_payment']);

        $this->assertTrue($c2List['allow_non_member_booking']);
        $this->assertEquals('09:00', $c2List['non_member_booking_start_time']);
        $this->assertEquals('17:00', $c2List['non_member_booking_end_time']);
        $this->assertFalse($c2List['is_member']);
        $this->assertTrue($c2List['can_book']);
        $this->assertTrue($c2List['requires_payment']);

        // 3. Test GET player/clubs/{clubId}
        $responseDetail = $this->getJson(
            "/api/v1/player/clubs/{$this->club1->id}",
            ['Authorization' => "Bearer {$this->playerToken}"]
        );
        $responseDetail->assertOk();
        $detail = $responseDetail->json('data');

        $this->assertEquals($this->club1->id, $detail['id']);
        $this->assertTrue($detail['is_member']);
        $this->assertEquals('approved', $detail['membership_status']);
        $this->assertEquals('MEM-ALPHA-001', $detail['membership_number']);
        $this->assertTrue($detail['can_book']);
        $this->assertFalse($detail['requires_payment']);

        $this->assertIsArray($detail['working_hours']);
        $this->assertCount(7, $detail['working_hours']);
        $this->assertEquals('monday', $detail['working_hours'][0]['day']);
        $this->assertTrue($detail['working_hours'][0]['is_open']);
        $this->assertEquals('08:00', $detail['working_hours'][0]['opens_at']);
        $this->assertEquals('22:00', $detail['working_hours'][0]['closes_at']);
    }

    public function test_approved_member_booking_free_and_direct(): void
    {
        $court = Court::create([
            'club_id' => $this->club1->id,
            'name' => 'Court A',
            'status' => 'active',
            'price_per_hour' => 1200,
        ]);

        $slot = CourtTimeSlot::create([
            'club_id' => $this->club1->id,
            'court_id' => $court->id,
            'booking_date' => '2026-08-20',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'available',
            'price' => 1200,
        ]);

        // Submit booking for approved member - no payment details needed
        $response = $this->postJson(
            '/api/v1/player/bookings',
            [
                'club_id' => $this->club1->id,
                'court_id' => $court->id,
                'slot_id' => $slot->id,
                'booking_date' => '2026-08-20',
            ],
            ['Authorization' => "Bearer {$this->playerToken}"]
        );

        $response->assertCreated();
        $response->assertJsonPath('data.booking_status', 'confirmed');
        $response->assertJsonPath('data.payment_method', 'free_membership_allowance');
        
        $this->assertDatabaseHas('bookings', [
            'club_id' => $this->club1->id,
            'player_id' => $this->player1->id,
            'slot_id' => $slot->id,
            'booking_status' => 'confirmed',
            'payment_method' => 'free_membership_allowance',
            'payment_transaction_id' => 'FREE_MEMBER_BOOKING',
        ]);
    }

    public function test_non_member_booking_denied_when_disabled(): void
    {
        $court = Court::create([
            'club_id' => $this->club1->id,
            'name' => 'Court B',
            'status' => 'active',
            'price_per_hour' => 1000,
        ]);

        $slot = CourtTimeSlot::create([
            'club_id' => $this->club1->id,
            'court_id' => $court->id,
            'booking_date' => '2026-08-20',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'available',
            'price' => 1000,
        ]);

        // Setup another player who has no membership in Club 1
        $player2 = User::factory()->create(['role' => 'player', 'status' => 'active']);
        $playerToken2 = 'test-token-player2';
        $player2->api_access_token = hash('sha256', $playerToken2);
        $player2->save();

        $response = $this->postJson(
            '/api/v1/player/bookings',
            [
                'club_id' => $this->club1->id,
                'court_id' => $court->id,
                'slot_id' => $slot->id,
                'booking_date' => '2026-08-20',
                'payment_method' => 'card',
                'payment_transaction_id' => 'TXN-9988',
            ],
            ['Authorization' => "Bearer {$playerToken2}"]
        );

        $response->assertForbidden();
        $response->assertJsonPath('error.code', ApiErrorCode::NON_MEMBER_BOOKING_DISABLED);
    }

    public function test_non_member_booking_outside_daily_restriction_window(): void
    {
        $court = Court::create([
            'club_id' => $this->club2->id,
            'name' => 'Court C',
            'status' => 'active',
            'price_per_hour' => 1500,
        ]);

        // Slot start is 18:00 (outside non-member window 09:00 - 17:00)
        $slot = CourtTimeSlot::create([
            'club_id' => $this->club2->id,
            'court_id' => $court->id,
            'booking_date' => '2026-08-20',
            'start_time' => '18:00:00',
            'end_time' => '19:00:00',
            'status' => 'available',
            'price' => 1500,
        ]);

        $response = $this->postJson(
            '/api/v1/player/bookings',
            [
                'club_id' => $this->club2->id,
                'court_id' => $court->id,
                'slot_id' => $slot->id,
                'booking_date' => '2026-08-20',
                'payment_method' => 'card',
                'payment_transaction_id' => 'TXN-9988',
            ],
            ['Authorization' => "Bearer {$this->playerToken}"]
        );

        $response->assertUnprocessable();
        $response->assertJsonPath('error.code', ApiErrorCode::OUTSIDE_NON_MEMBER_WINDOW);
    }

    public function test_non_member_booking_inside_daily_restriction_window(): void
    {
        $court = Court::create([
            'club_id' => $this->club2->id,
            'name' => 'Court D',
            'status' => 'active',
            'price_per_hour' => 1500,
        ]);

        // Slot start is 10:00 (inside non-member window 09:00 - 17:00)
        $slot = CourtTimeSlot::create([
            'club_id' => $this->club2->id,
            'court_id' => $court->id,
            'booking_date' => '2026-08-20',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'available',
            'price' => 1500,
        ]);

        // Booking fails without payment details
        $responseFailed = $this->postJson(
            '/api/v1/player/bookings',
            [
                'club_id' => $this->club2->id,
                'court_id' => $court->id,
                'slot_id' => $slot->id,
                'booking_date' => '2026-08-20',
            ],
            ['Authorization' => "Bearer {$this->playerToken}"]
        );
        $responseFailed->assertStatus(422);

        // Booking succeeds with payment details
        $responseSuccess = $this->postJson(
            '/api/v1/player/bookings',
            [
                'club_id' => $this->club2->id,
                'court_id' => $court->id,
                'slot_id' => $slot->id,
                'booking_date' => '2026-08-20',
                'payment_method' => 'card',
                'payment_transaction_id' => 'TXN-ABC-123',
            ],
            ['Authorization' => "Bearer {$this->playerToken}"]
        );

        $responseSuccess->assertCreated();
        $responseSuccess->assertJsonPath('data.booking_status', 'pending');
        $responseSuccess->assertJsonPath('data.payment_method', 'card');
    }

    public function test_club_booking_detail_includes_complete_player_detail(): void
    {
        $clubToken = 'test-club-token-123';
        $this->club1->api_access_token = hash('sha256', $clubToken);
        $this->club1->save();

        $court = Court::create([
            'club_id' => $this->club1->id,
            'name' => 'Court A',
            'status' => 'active',
            'price_per_hour' => 1200,
        ]);

        $slot = CourtTimeSlot::create([
            'club_id' => $this->club1->id,
            'court_id' => $court->id,
            'booking_date' => '2026-08-20',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'available',
            'price' => 1200,
        ]);

        // Create booking
        $booking = \App\Models\Booking::create([
            'club_id' => $this->club1->id,
            'court_id' => $court->id,
            'player_id' => $this->player1->id,
            'slot_id' => $slot->id,
            'booking_date' => '2026-08-20',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_method' => 'free_membership_allowance',
            'payment_transaction_id' => 'FREE_MEMBER_BOOKING',
            'court_price' => 1200,
            'service_fee' => 0,
            'total_amount' => 1200,
            'currency' => 'PKR',
        ]);

        $response = $this->getJson(
            "/api/v1/club/bookings/{$booking->id}",
            ['Authorization' => "Bearer {$clubToken}"]
        );

        $response->assertOk();
        $response->assertJsonPath('data.player_detail.player_id', $this->player1->id);
        $response->assertJsonPath('data.player_detail.is_member', true);
        $response->assertJsonPath('data.player_detail.membership_status', 'approved');
        $response->assertJsonPath('data.player_detail.membership_number', 'MEM-ALPHA-001');
        $response->assertJsonPath('data.player_detail.can_pay', false); // Member doesn't pay
        $response->assertJsonPath('data.player_detail.gender', 'male');
        $response->assertJsonPath('data.player_detail.playing_level', 'intermediate');
        $response->assertJsonPath('data.player_detail.age', 26); // Born 2000-01-01, current time is 2026
    }
}

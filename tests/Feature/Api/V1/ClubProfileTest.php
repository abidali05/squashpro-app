<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\ClubWorkingHour;
use App\Models\ClubNonMemberWindow;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $club;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->club = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'club_name' => ' Lahore Squash Center',
            'non_member_booking_allowed' => true,
            'non_member_booking_start_time' => '09:00',
            'non_member_booking_end_time' => '17:00',
        ]);
        $this->club->assignRole('club');

        $plainToken = 'test-club-profile-token-7788';
        $this->club->api_access_token = hash('sha256', $plainToken);
        $this->club->save();
        $this->token = $plainToken;
    }

    public function test_get_club_profile_contains_day_wise_hours_and_non_member_booking(): void
    {
        // Seed day-wise working hours
        ClubWorkingHour::create([
            'club_id' => $this->club->id,
            'day' => 'monday',
            'is_open' => true,
            'opens_at' => '08:00',
            'closes_at' => '22:00',
        ]);
        ClubWorkingHour::create([
            'club_id' => $this->club->id,
            'day' => 'sunday',
            'is_open' => false,
            'opens_at' => null,
            'closes_at' => null,
        ]);

        // Seed day-wise non-member booking schedules
        ClubNonMemberWindow::create([
            'club_id' => $this->club->id,
            'day' => 'monday',
            'is_available' => true,
            'from_time' => '09:00',
            'to_time' => '17:00',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/club/profile');

        $response->assertOk();
        
        $data = $response->json('data');
        
        $this->assertIsArray($data['working_hours']);
        $mondayHour = collect($data['working_hours'])->firstWhere('day', 'monday');
        $this->assertNotNull($mondayHour);
        $this->assertTrue($mondayHour['is_open']);
        $this->assertEquals('08:00', $mondayHour['opens_at']);
        $this->assertEquals('22:00', $mondayHour['closes_at']);

        $sundayHour = collect($data['working_hours'])->firstWhere('day', 'sunday');
        $this->assertNotNull($sundayHour);
        $this->assertFalse($sundayHour['is_open']);
        $this->assertNull($sundayHour['opens_at']);
        $this->assertNull($sundayHour['closes_at']);

        $this->assertTrue($data['allow_non_member_booking']);
        $this->assertEquals('09:00', $data['non_member_booking_start_time']);
        $this->assertEquals('17:00', $data['non_member_booking_end_time']);

        $this->assertIsArray($data['non_member_booking_schedule']);
        $mondayWindow = collect($data['non_member_booking_schedule'])->firstWhere('day', 'monday');
        $this->assertNotNull($mondayWindow);
        $this->assertTrue($mondayWindow['is_available']);
        $this->assertEquals('09:00', $mondayWindow['from_time']);
        $this->assertEquals('17:00', $mondayWindow['to_time']);
    }
}

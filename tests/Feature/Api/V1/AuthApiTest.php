<?php

namespace Tests\Feature\Api\V1;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_player_registration_and_login_flow(): void
    {
        $registerResponse = $this->postJson('/api/v1/auth/register/player', [
            'full_name' => 'Ali Khan',
            'email' => 'ali@example.com',
            'phone' => '+923001234567',
            'password' => 'Password@123',
        ]);

        $registerResponse->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.status', 'otp_pending');

        $loginBeforeOtp = $this->postJson('/api/v1/auth/login', [
            'email' => 'ali@example.com',
            'password' => 'Password@123',
            'role' => 'player',
        ]);

        $loginBeforeOtp->assertStatus(403)
            ->assertJsonPath('error_code', 'OTP_NOT_VERIFIED');

        DB::table('auth_otps')
            ->where('email', 'ali@example.com')
            ->update(['otp_hash' => Hash::make('123456')]);

        $verifyOtpResponse = $this->postJson('/api/v1/auth/verify-otp', [
            'email' => 'ali@example.com',
            'otp' => '123456',
            'purpose' => 'registration',
        ]);

        $verifyOtpResponse->assertOk()
            ->assertJsonPath('data.status', 'profile_incomplete');
    }

    public function test_forgot_password_reset_flow(): void
    {
        $this->postJson('/api/v1/auth/register/player', [
            'full_name' => 'Ali Khan',
            'email' => 'ali@example.com',
            'phone' => '+923001234567',
            'password' => 'Password@123',
        ])->assertCreated();

        $forgot = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'ali@example.com',
        ]);

        $forgot->assertOk()->assertJsonPath('success', true);

        DB::table('auth_otps')
            ->where('email', 'ali@example.com')
            ->where('purpose', 'forgot_password')
            ->update(['otp_hash' => Hash::make('654321')]);

        $verify = $this->postJson('/api/v1/auth/forgot-password/verify-otp', [
            'email' => 'ali@example.com',
            'otp' => '654321',
        ]);

        $verify->assertOk();
        $resetToken = $verify->json('data.reset_token');

        $reset = $this->postJson('/api/v1/auth/reset-password', [
            'reset_token' => $resetToken,
            'new_password' => 'NewPassword@123',
            'confirm_password' => 'NewPassword@123',
        ]);

        $reset->assertOk()->assertJsonPath('success', true);
    }

    public function test_player_registration_with_club_memberships(): void
    {
        $activeClub = \App\Models\User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'club_name' => 'Active Squash Club',
        ]);
        
        $inactiveClub = \App\Models\User::factory()->create([
            'role' => 'club',
            'status' => 'pending',
            'club_name' => 'Pending Squash Club',
        ]);

        $response = $this->postJson('/api/v1/auth/register/player', [
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+923001234568',
            'password' => 'Password@123',
            'club_memberships' => [
                [
                    'club_id' => $activeClub->id,
                    'membership_number' => 'MEM-12345',
                ]
            ]
        ]);

        $response->assertCreated();
        
        $this->assertDatabaseHas('club_membership_requests', [
            'club_id' => $activeClub->id,
            'membership_number' => 'MEM-12345',
            'status' => 'pending',
        ]);

        $responseInactive = $this->postJson('/api/v1/auth/register/player', [
            'full_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+923001234569',
            'password' => 'Password@123',
            'club_memberships' => [
                [
                    'club_id' => $inactiveClub->id,
                    'membership_number' => 'MEM-12345',
                ]
            ]
        ]);

        $responseInactive->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR');

        $responseDuplicate = $this->postJson('/api/v1/auth/register/player', [
            'full_name' => 'Duplicate Club',
            'email' => 'duplicate@example.com',
            'phone' => '+923001234510',
            'password' => 'Password@123',
            'club_memberships' => [
                [
                    'club_id' => $activeClub->id,
                    'membership_number' => 'MEM-1',
                ],
                [
                    'club_id' => $activeClub->id,
                    'membership_number' => 'MEM-2',
                ]
            ]
        ]);

        $responseDuplicate->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR');
    }

    public function test_player_registration_duplicate_email_or_phone_conflict(): void
    {
        \App\Models\User::factory()->create([
            'email' => 'existing@example.com',
            'phone' => '+923007654321',
            'name' => 'Existing User',
            'password' => 'Password@123',
        ]);

        $responseDuplicateEmail = $this->postJson('/api/v1/auth/register/player', [
            'full_name' => 'New Name',
            'email' => 'existing@example.com',
            'phone' => '+923001111111',
            'password' => 'Password@123',
        ]);

        $responseDuplicateEmail->assertStatus(409)
            ->assertJsonPath('error_code', 'EMAIL_ALREADY_EXISTS');

        $responseDuplicatePhone = $this->postJson('/api/v1/auth/register/player', [
            'full_name' => 'New Name',
            'email' => 'new@example.com',
            'phone' => '+923007654321',
            'password' => 'Password@123',
        ]);

        $responseDuplicatePhone->assertStatus(409)
            ->assertJsonPath('error_code', 'PHONE_ALREADY_EXISTS');
    }

    public function test_club_registration_with_booking_policy_and_initial_players(): void
    {
        $player1 = \App\Models\User::factory()->create([
            'role' => 'player',
            'status' => 'active',
        ]);

        $player2 = \App\Models\User::factory()->create([
            'role' => 'player',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/register/club', [
            'club_name' => 'Model Squash Club',
            'owner_manager_name' => 'Manager Name',
            'email' => 'club@example.com',
            'phone' => '+923001234588',
            'address' => 'Test Address',
            'city' => 'Lahore',
            'number_of_courts' => 5,
            'working_hours' => '09:00-22:00',
            'password' => 'Password@123',
            'non_member_booking' => [
                'allowed' => true,
                'start_time' => '10:00',
                'end_time' => '16:00',
                'timezone' => 'Asia/Karachi',
            ],
            'initial_player_ids' => [$player1->id, $player2->id],
        ]);

        $response->assertCreated();

        $clubId = $response->json('data.user_id');
        $this->assertDatabaseHas('users', [
            'id' => $clubId,
            'non_member_booking_allowed' => true,
            'non_member_booking_start_time' => '10:00',
            'non_member_booking_end_time' => '16:00',
            'timezone' => 'Asia/Karachi',
        ]);

        $this->assertDatabaseHas('club_membership_requests', [
            'club_id' => $clubId,
            'player_id' => $player1->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('club_membership_requests', [
            'club_id' => $clubId,
            'player_id' => $player2->id,
            'status' => 'pending',
        ]);

        $responseInvalid = $this->postJson('/api/v1/auth/register/club', [
            'club_name' => 'Model Squash Club 2',
            'owner_manager_name' => 'Manager Name',
            'email' => 'club2@example.com',
            'phone' => '+923001234589',
            'address' => 'Test Address',
            'city' => 'Lahore',
            'number_of_courts' => 5,
            'working_hours' => '09:00-22:00',
            'password' => 'Password@123',
            'non_member_booking' => [
                'allowed' => true,
            ],
        ]);

        $responseInvalid->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR');

        $responseInvalidFalse = $this->postJson('/api/v1/auth/register/club', [
            'club_name' => 'Model Squash Club 3',
            'owner_manager_name' => 'Manager Name',
            'email' => 'club3@example.com',
            'phone' => '+923001234590',
            'address' => 'Test Address',
            'city' => 'Lahore',
            'number_of_courts' => 5,
            'working_hours' => '09:00-22:00',
            'password' => 'Password@123',
            'non_member_booking' => [
                'allowed' => false,
                'start_time' => '10:00',
            ],
        ]);

        $responseInvalidFalse->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR');
    }

    public function test_club_registration_duplicate_email_or_phone_conflict(): void
    {
        \App\Models\User::factory()->create([
            'email' => 'club-existing@example.com',
            'phone' => '+923009999999',
            'role' => 'club',
            'club_name' => 'Existing Club',
            'password' => 'Password@123',
        ]);

        $responseEmail = $this->postJson('/api/v1/auth/register/club', [
            'club_name' => 'Model Squash Club 4',
            'owner_manager_name' => 'Manager Name',
            'email' => 'club-existing@example.com',
            'phone' => '+923001234591',
            'address' => 'Test Address',
            'city' => 'Lahore',
            'number_of_courts' => 5,
            'working_hours' => '09:00-22:00',
            'password' => 'Password@123',
        ]);

        $responseEmail->assertStatus(409)
            ->assertJsonPath('error_code', 'EMAIL_ALREADY_EXISTS');

        $responsePhone = $this->postJson('/api/v1/auth/register/club', [
            'club_name' => 'Model Squash Club 4',
            'owner_manager_name' => 'Manager Name',
            'email' => 'club-new@example.com',
            'phone' => '+923009999999',
            'address' => 'Test Address',
            'city' => 'Lahore',
            'number_of_courts' => 5,
            'working_hours' => '09:00-22:00',
            'password' => 'Password@123',
        ]);

        $responsePhone->assertStatus(409)
            ->assertJsonPath('error_code', 'PHONE_ALREADY_EXISTS');
    }

    public function test_club_registration_with_members_payload(): void
    {
        $player1 = \App\Models\User::factory()->create([
            'role' => 'player',
            'status' => 'active',
        ]);

        $player2 = \App\Models\User::factory()->create([
            'role' => 'player',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/register/club', [
            'club_name' => 'Members Test Club',
            'owner_manager_name' => 'Manager Name',
            'email' => 'members-club@example.com',
            'phone' => '+923001234599',
            'address' => 'Test Address',
            'city' => 'Lahore',
            'number_of_courts' => 5,
            'working_hours' => '09:00-22:00',
            'password' => 'Password@123',
            'members' => [
                [
                    'player_id' => $player1->id,
                    'membership_number' => 'CSC-1045',
                ],
                [
                    'player_id' => $player2->id,
                    'membership_number' => 'CSC-1188',
                ],
            ],
        ]);

        $response->assertCreated();

        $clubId = $response->json('data.user_id');

        // Verify they are saved directly as approved in club_memberships
        $this->assertDatabaseHas('club_memberships', [
            'club_id' => $clubId,
            'player_id' => $player1->id,
            'membership_number' => 'CSC-1045',
            'status' => 'approved',
            'verification_mode' => 'club_registration',
        ]);

        $this->assertDatabaseHas('club_memberships', [
            'club_id' => $clubId,
            'player_id' => $player2->id,
            'membership_number' => 'CSC-1188',
            'status' => 'approved',
            'verification_mode' => 'club_registration',
        ]);

        // Audit log exists
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $clubId,
            'action' => 'add_member',
            'entity_type' => \App\Models\ClubMembership::class,
        ]);

        // Validation test: duplicate player in members array
        $responseDuplicate = $this->postJson('/api/v1/auth/register/club', [
            'club_name' => 'Members Test Club 2',
            'owner_manager_name' => 'Manager Name',
            'email' => 'members-club-2@example.com',
            'phone' => '+923001234577',
            'address' => 'Test Address',
            'city' => 'Lahore',
            'number_of_courts' => 5,
            'working_hours' => '09:00-22:00',
            'password' => 'Password@123',
            'members' => [
                [
                    'player_id' => $player1->id,
                    'membership_number' => 'CSC-1045',
                ],
                [
                    'player_id' => $player1->id, // Duplicate player_id
                    'membership_number' => 'CSC-1188',
                ],
            ],
        ]);

        $responseDuplicate->assertStatus(422)
            ->assertJsonPath('message', 'Validation failed.');
    }

    public function test_day_wise_club_registration_flow(): void
    {
        // 1. Successful day-wise registration
        $workingHours = [
            ['day' => 'monday', 'is_open' => true, 'opens_at' => '10:00', 'closes_at' => '23:00'],
            ['day' => 'tuesday', 'is_open' => true, 'opens_at' => '09:00', 'closes_at' => '22:00'],
            ['day' => 'wednesday', 'is_open' => true, 'opens_at' => '09:00', 'closes_at' => '22:00'],
            ['day' => 'thursday', 'is_open' => true, 'opens_at' => '09:00', 'closes_at' => '22:00'],
            ['day' => 'friday', 'is_open' => true, 'opens_at' => '09:00', 'closes_at' => '22:00'],
            ['day' => 'saturday', 'is_open' => true, 'opens_at' => '09:00', 'closes_at' => '22:00'],
            ['day' => 'sunday', 'is_open' => false, 'opens_at' => null, 'closes_at' => null],
        ];

        $nonMemberSchedule = [
            [
                'day' => 'monday',
                'is_available' => true,
                'time_ranges' => [
                    ['from' => '10:00', 'to' => '11:00'],
                    ['from' => '15:00', 'to' => '16:00'],
                ]
            ],
            ['day' => 'tuesday', 'is_available' => false, 'time_ranges' => []],
            ['day' => 'wednesday', 'is_available' => false, 'time_ranges' => []],
            ['day' => 'thursday', 'is_available' => false, 'time_ranges' => []],
            ['day' => 'friday', 'is_available' => false, 'time_ranges' => []],
            ['day' => 'saturday', 'is_available' => false, 'time_ranges' => []],
            ['day' => 'sunday', 'is_available' => false, 'time_ranges' => []],
        ];

        $payload = [
            'club_name' => 'Daywise Test Club',
            'owner_manager_name' => 'Daywise Manager',
            'email' => 'daywise-club@example.com',
            'phone' => '+923009876543',
            'address' => '45-B Model Town',
            'city' => 'Lahore',
            'number_of_courts' => 4,
            'password' => 'Password@123',
            'timezone' => 'Asia/Karachi',
            'working_hours' => $workingHours,
            'allow_non_member_booking' => true,
            'non_member_booking_schedule' => $nonMemberSchedule,
        ];

        $response = $this->postJson('/api/v1/auth/register/club', $payload);
        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $clubId = $response->json('data.user_id');

        // Verify working hours stored in DB
        $this->assertDatabaseHas('club_working_hours', [
            'club_id' => $clubId,
            'day' => 'monday',
            'is_open' => true,
            'opens_at' => '10:00',
            'closes_at' => '23:00',
        ]);
        $this->assertDatabaseHas('club_working_hours', [
            'club_id' => $clubId,
            'day' => 'sunday',
            'is_open' => false,
            'opens_at' => null,
            'closes_at' => null,
        ]);

        // Verify non-member windows stored in DB
        $this->assertDatabaseHas('club_non_member_windows', [
            'club_id' => $clubId,
            'day' => 'monday',
            'is_available' => true,
            'from_time' => '10:00',
            'to_time' => '11:00',
        ]);
        $this->assertDatabaseHas('club_non_member_windows', [
            'club_id' => $clubId,
            'day' => 'monday',
            'is_available' => true,
            'from_time' => '15:00',
            'to_time' => '16:00',
        ]);

        // 2. Validation test: duplicate day entries in working hours
        $invalidWorkingHours = $workingHours;
        $invalidWorkingHours[6]['day'] = 'monday'; // Duplicate Monday

        $payloadInvalid1 = $payload;
        $payloadInvalid1['email'] = 'daywise-club-inv1@example.com';
        $payloadInvalid1['phone'] = '+923009876544';
        $payloadInvalid1['working_hours'] = $invalidWorkingHours;

        $responseInvalid1 = $this->postJson('/api/v1/auth/register/club', $payloadInvalid1);
        $responseInvalid1->assertStatus(422)
            ->assertJsonValidationErrors(['working_hours.6.day']);

        // 3. Validation test: overlapping non-member booking ranges
        $invalidNonMemberSchedule = $nonMemberSchedule;
        $invalidNonMemberSchedule[0]['time_ranges'] = [
            ['from' => '10:00', 'to' => '12:00'],
            ['from' => '11:30', 'to' => '13:00'], // Overlaps
        ];

        $payloadInvalid2 = $payload;
        $payloadInvalid2['email'] = 'daywise-club-inv2@example.com';
        $payloadInvalid2['phone'] = '+923009876545';
        $payloadInvalid2['non_member_booking_schedule'] = $invalidNonMemberSchedule;

        $responseInvalid2 = $this->postJson('/api/v1/auth/register/club', $payloadInvalid2);
        $responseInvalid2->assertStatus(422)
            ->assertJsonValidationErrors(['non_member_booking_schedule.0.time_ranges.1.from']);

        // 4. Validation test: non-member booking range outside club hours
        $invalidNonMemberSchedule2 = $nonMemberSchedule;
        $invalidNonMemberSchedule2[0]['time_ranges'] = [
            ['from' => '09:00', 'to' => '11:00'], // Monday opens at 10:00
        ];

        $payloadInvalid3 = $payload;
        $payloadInvalid3['email'] = 'daywise-club-inv3@example.com';
        $payloadInvalid3['phone'] = '+923009876546';
        $payloadInvalid3['non_member_booking_schedule'] = $invalidNonMemberSchedule2;

        $responseInvalid3 = $this->postJson('/api/v1/auth/register/club', $payloadInvalid3);
        $responseInvalid3->assertStatus(422)
            ->assertJsonValidationErrors(['non_member_booking_schedule.0.time_ranges.0.from']);

        // 5. Validation test: closed-day containing non-member booking ranges
        $invalidNonMemberSchedule3 = $nonMemberSchedule;
        $invalidNonMemberSchedule3[6] = [ // Sunday is closed
            'day' => 'sunday',
            'is_available' => true,
            'time_ranges' => [
                ['from' => '10:00', 'to' => '11:00'],
            ]
        ];

        $payloadInvalid4 = $payload;
        $payloadInvalid4['email'] = 'daywise-club-inv4@example.com';
        $payloadInvalid4['phone'] = '+923009876547';
        $payloadInvalid4['non_member_booking_schedule'] = $invalidNonMemberSchedule3;

        $responseInvalid4 = $this->postJson('/api/v1/auth/register/club', $payloadInvalid4);
        $responseInvalid4->assertStatus(422)
            ->assertJsonValidationErrors(['non_member_booking_schedule.6.is_available']);
    }

    public function test_player_registration_with_scorer_umpire_flags(): void
    {
        $response = $this->postJson('/api/v1/auth/register/player', [
            'full_name' => 'Adnan Scorer',
            'email' => 'adnan-scorer@example.com',
            'phone' => '+923331112223',
            'password' => 'password123',
            'are_you_scorer' => true,
            'are_you_umpire' => false,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('success', true);

        // Verify in DB
        $user = \App\Models\User::where('email', 'adnan-scorer@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->are_you_scorer);
        $this->assertFalse($user->are_you_umpire);
    }
}

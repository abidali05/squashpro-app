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
}

<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Court;
use App\Models\ClubWorkingHour;
use App\Models\CourtSlot;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourtManagementTest extends TestCase
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
        ]);

        // Seed day-wise working hours for this club
        // Monday: 09:00 to 18:00
        ClubWorkingHour::create([
            'club_id' => $this->club->id,
            'day' => 'monday',
            'is_open' => true,
            'opens_at' => '09:00',
            'closes_at' => '18:00',
        ]);

        // Sunday: Closed
        ClubWorkingHour::create([
            'club_id' => $this->club->id,
            'day' => 'sunday',
            'is_open' => false,
            'opens_at' => null,
            'closes_at' => null,
        ]);

        // Setup API access token
        $plainToken = 'test-club-token-45678';
        $this->club->api_access_token = hash('sha256', $plainToken);
        $this->club->save();
        $this->token = $plainToken;
    }

    public function test_create_court_successfully_with_day_wise_slots(): void
    {
        $response = $this->postJson(
            '/api/v1/club/courts',
            [
                'name' => 'Premium Glass Court 1',
                'type' => 'glass',
                'price_per_hour' => 1200,
                'status' => 'available',
                'slots' => [
                    [
                        'day' => 'monday',
                        'start_time' => '10:00',
                        'end_time' => '11:00',
                        'price' => 1000,
                        'is_available' => true,
                    ],
                    [
                        'day' => 'monday',
                        'start_time' => '12:00',
                        'end_time' => '13:30',
                        'price' => 1200,
                        'is_available' => true,
                    ]
                ]
            ],
            [
                'Authorization' => "Bearer {$this->token}",
            ]
        );

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure(['data' => ['court_id', 'slots']]);

        $courtId = $response->json('data.court_id');

        // Verify in DB
        $this->assertDatabaseHas('courts', [
            'id' => $courtId,
            'name' => 'Premium Glass Court 1',
            'club_id' => $this->club->id,
        ]);

        $this->assertDatabaseHas('court_slots', [
            'court_id' => $courtId,
            'day' => 'monday',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'price' => 1000.00,
        ]);

        $this->assertDatabaseHas('court_slots', [
            'court_id' => $courtId,
            'day' => 'monday',
            'start_time' => '12:00',
            'end_time' => '13:30',
            'price' => 1200.00,
        ]);
    }

    public function test_create_court_fails_when_slots_exceed_working_hours(): void
    {
        // Monday closes at 18:00, slot 17:30 - 18:30 exceeds bounds
        $response = $this->postJson(
            '/api/v1/club/courts',
            [
                'name' => 'Premium Glass Court 1',
                'type' => 'glass',
                'price_per_hour' => 1200,
                'status' => 'available',
                'slots' => [
                    [
                        'day' => 'monday',
                        'start_time' => '17:30',
                        'end_time' => '18:30',
                        'price' => 1000,
                    ]
                ]
            ],
            [
                'Authorization' => "Bearer {$this->token}",
            ]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slots.0.start_time']);
    }

    public function test_create_court_fails_when_club_is_closed_on_that_day(): void
    {
        // Sunday is closed, no slots should be allowed
        $response = $this->postJson(
            '/api/v1/club/courts',
            [
                'name' => 'Premium Glass Court 1',
                'type' => 'glass',
                'price_per_hour' => 1200,
                'status' => 'available',
                'slots' => [
                    [
                        'day' => 'sunday',
                        'start_time' => '10:00',
                        'end_time' => '11:00',
                        'price' => 1000,
                    ]
                ]
            ],
            [
                'Authorization' => "Bearer {$this->token}",
            ]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slots.0.day']);
    }

    public function test_create_court_fails_on_overlapping_slots_same_day(): void
    {
        $response = $this->postJson(
            '/api/v1/club/courts',
            [
                'name' => 'Premium Glass Court 1',
                'type' => 'glass',
                'price_per_hour' => 1200,
                'status' => 'available',
                'slots' => [
                    [
                        'day' => 'monday',
                        'start_time' => '10:00',
                        'end_time' => '11:30',
                        'price' => 1000,
                    ],
                    [
                        'day' => 'monday',
                        'start_time' => '11:00', // overlaps with 10:00 - 11:30
                        'end_time' => '12:00',
                        'price' => 1100,
                    ]
                ]
            ],
            [
                'Authorization' => "Bearer {$this->token}",
            ]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slots.1.start_time']);
    }

    public function test_create_court_fails_on_non_positive_price(): void
    {
        $response = $this->postJson(
            '/api/v1/club/courts',
            [
                'name' => 'Premium Glass Court 1',
                'type' => 'glass',
                'price_per_hour' => 1200,
                'status' => 'available',
                'slots' => [
                    [
                        'day' => 'monday',
                        'start_time' => '10:00',
                        'end_time' => '11:00',
                        'price' => 0, // must be positive
                    ]
                ]
            ],
            [
                'Authorization' => "Bearer {$this->token}",
            ]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slots.0.price']);
    }

    public function test_update_court_successfully_replaces_slots(): void
    {
        // 1. Create a court with 1 slot initially
        $court = Court::create([
            'club_id' => $this->club->id,
            'name' => 'Premium Glass Court 1',
            'type' => 'glass',
            'price_per_hour' => 1200,
            'status' => 'active',
        ]);

        CourtSlot::create([
            'court_id' => $court->id,
            'day' => 'monday',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'price' => 1000,
            'is_available' => true,
        ]);

        // 2. Update it with new slots array
        $response = $this->postJson(
            "/api/v1/club/courts/{$court->id}/edit",
            [
                'name' => 'Renamed Court',
                'slots' => [
                    [
                        'day' => 'monday',
                        'start_time' => '14:00',
                        'end_time' => '15:30',
                        'price' => 1500,
                    ]
                ]
            ],
            [
                'Authorization' => "Bearer {$this->token}",
            ]
        );

        $response->assertOk();

        // Old slot should be deleted
        $this->assertDatabaseMissing('court_slots', [
            'court_id' => $court->id,
            'start_time' => '10:00',
        ]);

        // New slot should be created
        $this->assertDatabaseHas('court_slots', [
            'court_id' => $court->id,
            'day' => 'monday',
            'start_time' => '14:00',
            'end_time' => '15:30',
            'price' => 1500.00,
        ]);
    }

    public function test_club_timing_update_flags_court_for_maintenance_and_logs_audit(): void
    {
        // 1. Create a court that has status 'active' and configured slots on Monday (10:00 - 11:00)
        $court = Court::create([
            'club_id' => $this->club->id,
            'name' => 'Flagged Court',
            'type' => 'glass',
            'price_per_hour' => 1200,
            'status' => 'active',
        ]);

        CourtSlot::create([
            'court_id' => $court->id,
            'day' => 'monday',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'price' => 1000,
            'is_available' => true,
        ]);

        // 2. Update club working hours so that Monday opens at 11:30 (now 10:00-11:00 is outside new working hours)
        $response = $this->postJson(
            '/api/v1/club/details/update',
            [
                'working_hours' => [
                    [
                        'day' => 'monday',
                        'is_open' => true,
                        'opens_at' => '11:30',
                        'closes_at' => '18:00',
                    ],
                    [
                        'day' => 'tuesday',
                        'is_open' => true,
                        'opens_at' => '09:00',
                        'closes_at' => '18:00',
                    ],
                    [
                        'day' => 'wednesday',
                        'is_open' => true,
                        'opens_at' => '09:00',
                        'closes_at' => '18:00',
                    ],
                    [
                        'day' => 'thursday',
                        'is_open' => true,
                        'opens_at' => '09:00',
                        'closes_at' => '18:00',
                    ],
                    [
                        'day' => 'friday',
                        'is_open' => true,
                        'opens_at' => '09:00',
                        'closes_at' => '18:00',
                    ],
                    [
                        'day' => 'saturday',
                        'is_open' => true,
                        'opens_at' => '09:00',
                        'closes_at' => '18:00',
                    ],
                    [
                        'day' => 'sunday',
                        'is_open' => false,
                        'opens_at' => null,
                        'closes_at' => null,
                    ]
                ]
            ],
            [
                'Authorization' => "Bearer {$this->token}",
            ]
        );

        $response->assertOk();

        // 3. Assert court status is updated to maintenance
        $court->refresh();
        $this->assertEquals('maintenance', $court->status);
        $this->assertEquals('Court moved to maintenance because configured slots are outside the updated club working hours.', $court->maintenance_note);

        // 4. Assert audit log is recorded in court_status_audits
        $this->assertDatabaseHas('court_status_audits', [
            'court_id' => $court->id,
            'previous_status' => 'active',
            'new_status' => 'maintenance',
            'reason' => 'Court moved to maintenance because configured slots are outside the updated club working hours.',
        ]);
    }
}

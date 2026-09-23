<?php

namespace Tests\Feature\Api\V1;

use App\Models\Court;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCourtApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_can_get_courts_by_club_id(): void
    {
        $club = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'club_name' => 'Test Squash Club',
        ]);

        $court1 = Court::create([
            'club_id' => $club->id,
            'name' => 'Court 1',
            'type' => 'glass',
            'price_per_hour' => 20.00,
            'capacity' => 2,
            'status' => 'active',
            'description' => 'Championship Court',
        ]);

        $court2 = Court::create([
            'club_id' => $club->id,
            'name' => 'Court 2',
            'type' => 'wooden',
            'price_per_hour' => 15.00,
            'capacity' => 2,
            'status' => 'active',
            'description' => 'Practice Court',
        ]);

        // Test route /api/v1/clubs/{club_id}/courts
        $response = $this->getJson("/api/v1/clubs/{$club->id}/courts");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Courts retrieved successfully.',
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.court_id', $court1->id)
            ->assertJsonPath('data.0.name', 'Court 1')
            ->assertJsonPath('data.0.type', 'Glass')
            ->assertJsonPath('data.1.court_id', $court2->id)
            ->assertJsonPath('data.1.name', 'Court 2');

        // Test alternative route /api/v1/courts/{club_id}
        $responseAlt = $this->getJson("/api/v1/courts/{$club->id}");

        $responseAlt->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_returns_404_when_club_not_found(): void
    {
        $response = $this->getJson("/api/v1/clubs/99999/courts");

        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'Club not found.',
                'error_code' => 'CLUB_NOT_FOUND',
            ]);
    }
}

<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPlayerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_public_player_list_endpoint_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/players');
        $response->assertOk();
    }

    public function test_public_player_list_filters_and_paginates_players(): void
    {
        // Seed 3 active players and 1 pending player
        $player1 = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'name' => 'Zeeshan Ali',
            'email' => 'zeeshan@example.com',
            'phone' => '+923001111111',
            'created_at' => now()->subDays(2),
        ]);

        $player2 = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'name' => 'Asad Khan',
            'email' => 'asad@example.com',
            'phone' => '+923002222222',
            'created_at' => now()->subDay(),
        ]);

        $player3 = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'name' => 'Babar Azam',
            'email' => 'babar@example.com',
            'phone' => '+923003333333',
            'created_at' => now(),
        ]);

        $pendingPlayer = User::factory()->create([
            'role' => 'player',
            'status' => 'pending',
            'name' => 'Pending Player',
        ]);

        // Default query: active players sorted by name asc
        $response = $this->getJson('/api/v1/players');
        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.full_name', 'Asad Khan')
            ->assertJsonPath('data.1.full_name', 'Babar Azam')
            ->assertJsonPath('data.2.full_name', 'Zeeshan Ali')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'full_name',
                        'profile_image_url',
                    ]
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'from',
                    'to',
                ]
            ]);

        // Search by name
        $responseSearch = $this->getJson('/api/v1/players?search=Zeeshan');
        $responseSearch->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.full_name', 'Zeeshan Ali');

        // Search by email
        $responseSearchEmail = $this->getJson('/api/v1/players?search=asad@example.com');
        $responseSearchEmail->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.full_name', 'Asad Khan');

        // Search by phone
        $responseSearchPhone = $this->getJson('/api/v1/players?search=+923003333333');
        $responseSearchPhone->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.full_name', 'Babar Azam');

        // Sort by created_at descending
        $responseSort = $this->getJson('/api/v1/players?sort=-created_at');
        $responseSort->assertOk()
            ->assertJsonPath('data.0.full_name', 'Babar Azam')
            ->assertJsonPath('data.1.full_name', 'Asad Khan')
            ->assertJsonPath('data.2.full_name', 'Zeeshan Ali');

        // Pagination
        $responsePaginated = $this->getJson('/api/v1/players?per_page=2');
        $responsePaginated->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.last_page', 2);
    }
}

<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicClubApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_public_club_list_endpoint_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/clubs');
        $response->assertOk();
    }

    public function test_public_club_list_filters_and_paginates_active_clubs(): void
    {
        // Seed 3 active clubs and 1 inactive (pending) club
        $club1 = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'club_name' => 'Lahore Squash Academy',
            'city' => 'Lahore',
            'address' => 'Gaddafi Stadium, Lahore',
            'created_at' => now()->subDays(2),
        ]);

        $club2 = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'club_name' => 'Islamabad Club',
            'city' => 'Islamabad',
            'address' => 'G-6, Islamabad',
            'created_at' => now()->subDay(),
        ]);

        $club3 = User::factory()->create([
            'role' => 'club',
            'status' => 'active',
            'club_name' => 'Lahore Gymkhana',
            'city' => 'Lahore',
            'address' => 'Mall Road, Lahore',
            'created_at' => now(),
        ]);

        $inactiveClub = User::factory()->create([
            'role' => 'club',
            'status' => 'pending',
            'club_name' => 'Karachi Club',
            'city' => 'Karachi',
            'address' => 'Clifton, Karachi',
        ]);

        // Default query: gets all active clubs sorted by club_name asc
        $response = $this->getJson('/api/v1/clubs');
        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.name', 'Islamabad Club')
            ->assertJsonPath('data.1.name', 'Lahore Gymkhana')
            ->assertJsonPath('data.2.name', 'Lahore Squash Academy')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'logo_url',
                        'city',
                        'address',
                        'status',
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

        // Search by name or city
        $responseSearch = $this->getJson('/api/v1/clubs?search=Lahore');
        $responseSearch->assertOk()
            ->assertJsonCount(2, 'data'); // Lahore Gymkhana and Lahore Squash Academy

        // Sort descending by name
        $responseSortDesc = $this->getJson('/api/v1/clubs?sort=-name');
        $responseSortDesc->assertOk()
            ->assertJsonPath('data.0.name', 'Lahore Squash Academy')
            ->assertJsonPath('data.1.name', 'Lahore Gymkhana')
            ->assertJsonPath('data.2.name', 'Islamabad Club');

        // Pagination: per_page = 2
        $responsePagination = $this->getJson('/api/v1/clubs?per_page=2');
        $responsePagination->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.last_page', 2);
    }
}

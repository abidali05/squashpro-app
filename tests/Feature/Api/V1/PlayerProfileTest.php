<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $player;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->player = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'are_you_scorer' => true,
            'are_you_umpire' => false,
        ]);
        $this->player->assignRole('player');

        $plainToken = 'test-player-profile-token-999';
        $this->player->api_access_token = hash('sha256', $plainToken);
        $this->player->save();
        $this->token = $plainToken;
    }

    public function test_player_profile_returns_scorer_and_umpire_flags(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/player/profile');

        $response->assertOk();
        $response->assertJsonPath('data.is_scorer', true);
        $response->assertJsonPath('data.is_umpire', false);
    }
}

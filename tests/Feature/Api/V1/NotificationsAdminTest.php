<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\AppNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $player;
    private AppNotification $notification;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        // Create admin user
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->admin->assignRole('admin');

        // Create player user
        $this->player = User::factory()->create([
            'role' => 'player',
            'status' => 'active',
            'name' => 'Notification Recipient',
        ]);

        // Create notification
        $this->notification = AppNotification::create([
            'user_id' => $this->player->id,
            'notifiable_type' => User::class,
            'notifiable_id' => $this->player->id,
            'title' => 'Test Notification Title',
            'description' => 'Test Notification Description',
            'type' => 'test_type',
            'role_type' => 'player',
            'data' => ['foo' => 'bar'],
            'read_at' => null,
        ]);
    }

    public function test_admin_can_access_notifications_index_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/notifications');

        $response->assertOk();
        $response->assertViewIs('content.admin.notifications.index');
        $response->assertSee('Test Notification Title');
        $response->assertSee('Notification Recipient');
    }

    public function test_admin_can_delete_notification_log(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete("/admin/notifications/{$this->notification->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('app_notifications', [
            'id' => $this->notification->id,
        ]);
    }
}

<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingReview;
use App\Models\Court;
use App\Models\CourtTimeSlot;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityAndReviewsAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $player;
    private User $club;
    private Court $court;
    private BookingReview $review;
    private AuditLog $log;

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

        // Create player & club
        $this->player = User::factory()->create(['role' => 'player', 'status' => 'active', 'name' => 'Reviewer Player']);
        $this->club = User::factory()->create(['role' => 'club', 'status' => 'active', 'club_name' => 'Club Alpha']);
        $this->court = Court::create([
            'club_id' => $this->club->id,
            'name' => 'Court A',
            'status' => 'active',
            'price_per_hour' => 1200,
        ]);

        // Create court time slot
        $slot = CourtTimeSlot::create([
            'club_id' => $this->club->id,
            'court_id' => $this->court->id,
            'booking_date' => '2026-08-20',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'booked', // Valid values: 'available', 'booked', 'blocked'
            'price' => 1200,
        ]);

        // Create booking
        $booking = Booking::create([
            'club_id' => $this->club->id,
            'court_id' => $this->court->id,
            'player_id' => $this->player->id,
            'slot_id' => $slot->id,
            'booking_date' => '2026-08-20',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'court_price' => 1200,
            'total_amount' => 1200,
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
        ]);

        // Create review
        $this->review = BookingReview::create([
            'booking_id' => $booking->id,
            'player_id' => $this->player->id,
            'club_id' => $this->club->id,
            'court_id' => $this->court->id,
            'rating' => 5,
            'review' => 'Excellent court conditions!',
        ]);

        // Create audit log
        $this->log = AuditLog::create([
            'actor_id' => $this->player->id,
            'action' => 'submit_booking_review',
            'entity_type' => BookingReview::class,
            'entity_id' => $this->review->id,
            'before' => null,
            'after' => ['rating' => 5],
        ]);
    }

    public function test_admin_can_access_activity_logs_index_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/audit-logs');

        $response->assertOk();
        $response->assertViewIs('content.admin.audit_logs.index');
        $response->assertSee('submit_booking_review');
        $response->assertSee('Reviewer Player');
    }

    public function test_admin_can_access_booking_reviews_index_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/booking-reviews');

        $response->assertOk();
        $response->assertViewIs('content.admin.reviews.index');
        $response->assertSee('Excellent court conditions!');
        $response->assertSee('Reviewer Player');
    }

    public function test_admin_can_delete_booking_review(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete("/admin/booking-reviews/{$this->review->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('booking_reviews', [
            'id' => $this->review->id,
        ]);
    }
}

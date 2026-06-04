<?php

namespace App\Notifications\Booking;

use App\Models\Booking;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BookingStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Booking $booking)
    {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return [FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $status = (string) $this->booking->booking_status;
        $title = match ($status) {
            'confirmed' => 'Booking confirmed',
            'cancelled' => 'Booking cancelled',
            default => 'Booking updated',
        };

        $action = match ($status) {
            'confirmed' => 'has been confirmed',
            'cancelled' => 'has been cancelled',
            default => 'was updated',
        };

        return [
            'type' => 'booking_status_updated',
            'title' => $title,
            'message' => sprintf(
                'Your booking for %s at %s %s by %s.',
                $this->booking->booking_date?->format('Y-m-d'),
                substr((string) $this->booking->start_time, 0, 5),
                $action,
                $this->booking->club->name,
            ),
            'data' => [
                'booking_id' => $this->booking->id,
                'booking_status' => $this->booking->booking_status,
                'club_id' => $this->booking->club_id,
                'player_id' => $this->booking->player_id,
            ],
        ];
    }

    public function toFcm(object $notifiable): array
    {
        $array = $this->toArray($notifiable);

        return [
            'type' => $array['type'],
            'title' => $array['title'],
            'message' => $array['message'],
            'data' => [
                ...$array['data'],
                'type' => $array['type'],
            ],
        ];
    }
}

<?php

namespace App\Notifications\Booking;

use App\Models\Booking;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BookingCancelledByPlayerNotification extends Notification implements ShouldQueue
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
        return [
            'type' => 'booking_cancelled_by_player',
            'title' => 'Booking cancelled',
            'message' => sprintf(
                '%s cancelled the booking for %s at %s.',
                $this->booking->player->name,
                $this->booking->booking_date?->format('Y-m-d'),
                substr((string) $this->booking->start_time, 0, 5),
            ),
            'data' => [
                'booking_id' => $this->booking->id,
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

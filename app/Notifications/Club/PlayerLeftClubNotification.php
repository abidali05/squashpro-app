<?php

namespace App\Notifications\Club;

use App\Models\User;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PlayerLeftClubNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly User $player,
        private readonly string $reason
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return [FcmChannel::class];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'player_left',
            'title' => 'Player Left Club',
            'message' => sprintf('Player %s has left your club. Reason: %s', $this->player->name, $this->reason),
            'data' => [
                'player_id' => $this->player->id,
                'reason' => $this->reason,
            ],
        ];
    }

    /**
     * Get the Firebase Cloud Messaging (FCM) representation of the notification.
     */
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

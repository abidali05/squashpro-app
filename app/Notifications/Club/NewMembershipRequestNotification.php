<?php

namespace App\Notifications\Club;

use App\Models\User;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewMembershipRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly User $player,
        private readonly string $membershipNumber
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
            'type' => 'membership_request',
            'title' => 'New Membership Request',
            'message' => sprintf('Player %s has requested to join your club with membership number %s.', $this->player->name, $this->membershipNumber),
            'data' => [
                'player_id' => $this->player->id,
                'membership_number' => $this->membershipNumber,
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

<?php

namespace App\Notifications\Club;

use App\Models\User;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MembershipRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly User $club,
        private readonly string $rejectionReason
    ) {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return [FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'membership_rejected',
            'title' => 'Membership Rejected',
            'message' => sprintf('Your membership request for %s was rejected. Reason: %s', $this->club->club_name ?? $this->club->name, $this->rejectionReason),
            'data' => [
                'club_id' => $this->club->id,
                'rejection_reason' => $this->rejectionReason,
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

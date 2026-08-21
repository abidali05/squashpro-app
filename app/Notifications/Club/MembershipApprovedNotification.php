<?php

namespace App\Notifications\Club;

use App\Models\User;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MembershipApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly User $club,
        private readonly string $membershipNumber
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
            'type' => 'membership_approved',
            'title' => 'Membership Approved',
            'message' => sprintf('Your membership request for %s has been approved.', $this->club->club_name ?? $this->club->name),
            'data' => [
                'club_id' => $this->club->id,
                'membership_number' => $this->membershipNumber,
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

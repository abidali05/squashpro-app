<?php

namespace App\Notifications\Court;

use App\Models\Court;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CourtMaintenanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Court $court)
    {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return [FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $clubName = $this->court->club?->club_name
            ?? $this->court->club?->name
            ?? 'A club';

        return [
            'type' => 'court_maintenance',
            'title' => 'Court under maintenance',
            'message' => sprintf('%s moved %s to maintenance.', $clubName, $this->court->name),
            'data' => [
                'court_id' => $this->court->id,
                'club_id' => $this->court->club_id,
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

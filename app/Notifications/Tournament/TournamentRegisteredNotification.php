<?php

namespace App\Notifications\Tournament;

use App\Models\TournamentRegistration;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TournamentRegisteredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly TournamentRegistration $registration)
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
            'type' => 'tournament_registered',
            'title' => 'New tournament registration',
            'message' => sprintf(
                '%s registered for %s.',
                $this->registration->player?->name ?? 'A player',
                $this->registration->tournament?->name ?? 'your tournament'
            ),
            'data' => [
                'registration_id' => $this->registration->id,
                'tournament_id' => $this->registration->tournament_id,
                'player_id' => $this->registration->player_id,
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

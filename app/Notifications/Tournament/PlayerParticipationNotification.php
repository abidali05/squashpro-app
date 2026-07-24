<?php

namespace App\Notifications\Tournament;

use App\Models\Tournament;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PlayerParticipationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Tournament $tournament, private readonly string $decision)
    {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return [FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $statusStr = strtolower($this->decision) === 'accept' ? 'accepted' : 'rejected';

        return [
            'type' => 'player_participation',
            'title' => 'Tournament Participation Status',
            'message' => sprintf(
                'You have successfully %s participation in the tournament "%s".',
                $statusStr,
                $this->tournament->name
            ),
            'data' => [
                'tournament_id' => $this->tournament->id,
                'decision' => $this->decision,
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

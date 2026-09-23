<?php

namespace App\Notifications\Tournament;

use App\Models\Tournament;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TournamentOpenToAllNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Tournament $tournament)
    {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return [FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $clubName = $this->tournament->club?->club_name
            ?? $this->tournament->club?->name
            ?? 'A club host';

        return [
            'type' => 'open_tournament_created',
            'title' => 'New Open Tournament Created',
            'message' => sprintf(
                '%s created an Open to All tournament: "%s" starting on %s.',
                $clubName,
                $this->tournament->name,
                $this->tournament->start_date?->format('Y-m-d')
            ),
            'data' => [
                'tournament_id' => $this->tournament->id,
                'club_id' => $this->tournament->club_id,
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

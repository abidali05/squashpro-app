<?php

namespace App\Notifications\Tournament;

use App\Models\Tournament;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TournamentInvitationNotification extends Notification implements ShouldQueue
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
        $organizerName = $this->tournament->club->club_name ?? $this->tournament->club->name;

        return [
            'type' => 'tournament_invitation',
            'title' => 'New Tournament Invitation',
            'message' => sprintf(
                'You have been invited to participate in the tournament "%s" by %s.',
                $this->tournament->name,
                $organizerName
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

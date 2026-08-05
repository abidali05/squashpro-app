<?php

namespace App\Notifications\Tournament;

use App\Models\Tournament;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TournamentInvitationAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Tournament $tournament, private readonly ?\App\Models\User $invitedClub = null)
    {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return [FcmChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        $opponentName = $this->invitedClub?->club_name
            ?? $this->invitedClub?->name
            ?? 'The invited club';

        return [
            'type' => 'tournament_invitation_accepted',
            'title' => 'Tournament Invitation Accepted',
            'message' => sprintf(
                '%s has accepted your invitation for the tournament "%s".',
                $opponentName,
                $this->tournament->name
            ),
            'data' => [
                'tournament_id' => $this->tournament->id,
                'invited_club_id' => $this->invitedClub?->id,
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

<?php

namespace App\Notifications\Channels;

use App\Models\AppNotification;
use App\Services\FirebaseService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class FcmChannel
{
    public function __construct(private readonly FirebaseService $firebaseService)
    {
    }

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        $token = $notifiable->routeNotificationFor('fcm', $notification);
        $payload = $notification->toFcm($notifiable);

        $this->createAppNotification($notifiable, $payload);

        if (empty($token)) {
            return;
        }

        try {
            $this->firebaseService->sendToToken(
                token: $token,
                title: (string) ($payload['title'] ?? ''),
                body: (string) ($payload['message'] ?? ''),
                data: (array) ($payload['data'] ?? [])
            );
        } catch (\Throwable $e) {
            Log::error('FCM notification failed', [
                'notification' => get_class($notification),
                'notifiable_id' => $notifiable->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function createAppNotification(object $notifiable, array $payload): void
    {
        if (! isset($notifiable->id) || ! isset($payload['title']) || ! isset($payload['message'])) {
            return;
        }

        AppNotification::create([
            'user_id' => $notifiable->id,
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->id,
            'title' => (string) $payload['title'],
            'description' => (string) ($payload['message'] ?? ''),
            'type' => (string) ($payload['type'] ?? 'booking_notification'),
            'data' => [
                'title' => (string) $payload['title'],
                'message' => (string) $payload['message'],
                ...((array) ($payload['data'] ?? [])),
            ],
            'read_at' => null,
        ]);
    }
}

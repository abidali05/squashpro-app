<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Services\FcmTokenService;
use App\Support\ApiErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function __construct(private readonly FcmTokenService $fcmTokenService) {}

    public function store_fcm_token(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed.');
        }

        $this->fcmTokenService->saveForUser($request->user(), [
            'fcm_token' => $request->string('fcm_token')->toString(),
        ]);

        return $this->success(null, 'Token saved successfully');
    }

    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->appNotifications()
            ->latest()
            ->get()
            ->map(fn(AppNotification $n) => $this->formatNotification($n))
            ->values();

        return $this->success($notifications, 'Notifications fetched successfully');
    }

    public function unread(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->appNotifications()
            ->whereNull('read_at')
            ->latest()
            ->get()
            ->map(fn(AppNotification $n) => $this->formatNotification($n))
            ->values();

        return $this->success($notifications, 'Unread notifications fetched successfully');
    }

    public function unread_count(Request $request): JsonResponse
    {
        $count = $request->user()
            ->appNotifications()
            ->whereNull('read_at')
            ->count();

        return $this->success(['count' => $count], 'Unread count fetched successfully');
    }

    public function mark_as_read(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->appNotifications()
            ->whereKey($id)
            ->first();

        if (! $notification) {
            return $this->notFound('Notification not found');
        }

        if ($notification->read_at === null) {
            $notification->read_at = now();
            $notification->save();
        }

        return $this->success($this->formatNotification($notification->fresh()), 'Notification marked as read');
    }

    public function mark_all_as_read(Request $request): JsonResponse
    {
        $request->user()
            ->appNotifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->success(null, 'All notifications marked as read');
    }

    private function formatNotification(AppNotification $notification): array
    {
        $data = is_array($notification->data)
            ? $notification->data
            : json_decode($notification->data ?? '{}', true);

        return [
            'id' => $notification->id,
            'type' => $data['type'] ?? null,
            'title' => $data['title'] ?? $notification->title ?? null,
            'message' => $data['message'] ?? $notification->description ?? null,
            'description' => $notification->description,
            'data' => $data,
            'read_at' => optional($notification->read_at)?->toDateTimeString(),
            'created_at' => optional($notification->created_at)?->toDateString(),
        ];
    }

    private function success(mixed $data, string $message = 'Success', int $status = 200): JsonResponse
    {
        return $this->successResponse($message, $data, $status);
    }

    private function validationError(mixed $errors, string $message = 'Validation failed.', int $status = 422): JsonResponse
    {
        if (is_object($errors) && method_exists($errors, 'toArray')) {
            $errors = $errors->toArray();
        }

        return $this->errorResponse($message, ApiErrorCode::VALIDATION_ERROR, (array) $errors, $status);
    }

    private function notFound(string $message = 'Not found'): JsonResponse
    {
        return $this->errorResponse($message, ApiErrorCode::RECORD_NOT_FOUND, [], 404);
    }
}

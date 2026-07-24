<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationManagementController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = in_array((int) $request->integer('per_page', 10), [10, 25, 50, 100], true)
            ? (int) $request->integer('per_page', 10) : 10;

        $search = trim($request->string('search')->toString());
        $roleType = trim($request->string('role_type')->toString());
        $status = trim($request->string('status')->toString());

        $notifications = AppNotification::query()
            ->leftJoin('users', 'app_notifications.user_id', '=', 'users.id')
            ->select('app_notifications.*')
            ->with(['notifiable'])
            ->when($search !== '', function (Builder $q) use ($search) {
                $q->where(function (Builder $sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%");
                });
            })
            ->when($roleType !== '', fn (Builder $q) => $q->where('app_notifications.role_type', $roleType))
            ->when($status !== '', function (Builder $q) use ($status) {
                if ($status === 'read') {
                    $q->whereNotNull('read_at');
                } elseif ($status === 'unread') {
                    $q->whereNull('read_at');
                }
            })
            ->orderBy('app_notifications.created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // Dynamically get all recipients for display name mapping in the view
        $userIds = $notifications->pluck('user_id')->filter()->unique()->toArray();
        $users = User::whereIn('id', $userIds)->get(['id', 'name', 'email', 'role'])->keyBy('id');

        return view('content.admin.notifications.index', compact(
            'notifications',
            'users',
            'search',
            'roleType',
            'status',
            'perPage'
        ));
    }

    public function destroy(AppNotification $notification): RedirectResponse
    {
        $notification->delete();

        return redirect()->route('admin.notifications.index')->with('success', 'Notification log deleted successfully.');
    }
}

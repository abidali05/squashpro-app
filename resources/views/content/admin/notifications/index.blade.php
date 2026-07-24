@extends('layouts/contentNavbarLayout')

@section('title', 'Notification Logs')

@section('content')
@component('admin.components.datatable', [
    'title'       => 'System Notifications',
    'subtitle'    => 'Track and audit user notifications dispatched by the system',
    'paginator'   => $notifications,
    'createUrl'   => null,
    'search'      => $search,
    'perPage'     => $perPage,
    'filters'     => [
        [
            'name'    => 'role_type',
            'label'   => 'Recipient Role',
            'value'   => $roleType,
            'options' => [
                ''       => 'All Roles',
                'player' => 'Player',
                'club'   => 'Club',
                'admin'  => 'Admin',
            ],
        ],
        [
            'name'    => 'status',
            'label'   => 'Read Status',
            'value'   => $status,
            'options' => [
                ''       => 'All Statuses',
                'read'   => 'Read',
                'unread' => 'Unread',
            ],
        ],
    ],
    'columns' => [
        ['label' => 'Recipient',     'sortable' => false],
        ['label' => 'Title',         'sortable' => false],
        ['label' => 'Description',   'sortable' => false],
        ['label' => 'Role Type',     'sortable' => false],
        ['label' => 'Status',        'sortable' => false],
        ['label' => 'Dispatched At', 'sortable' => false],
        ['label' => 'Actions',       'actions' => true],
    ],
])
    @forelse($notifications as $notif)
        <tr>
            <td>
                @if(isset($users[$notif->user_id]))
                    @php
                        $user = $users[$notif->user_id];
                    @endphp
                    <div>
                        <span class="fw-semibold d-block">{{ $user->name }}</span>
                        <small class="text-muted d-block">{{ $user->email }}</small>
                        <span class="badge bg-label-secondary text-uppercase" style="font-size:10px;">{{ $user->role }}</span>
                    </div>
                @else
                    <span class="text-muted">Guest / Unknown (ID: {{ $notif->user_id }})</span>
                @endif
            </td>
            <td>
                <span class="fw-semibold">{{ $notif->title }}</span>
            </td>
            <td style="max-width: 300px; white-space: normal;">
                <span class="small">{{ $notif->description }}</span>
            </td>
            <td>
                <span class="badge bg-label-info text-capitalize">{{ str_replace('_', ' ', $notif->role_type) }}</span>
            </td>
            <td>
                @if($notif->read_at)
                    <span class="badge bg-label-success">Read</span>
                    <div class="text-muted small" style="font-size: 10px;">{{ $notif->read_at->format('Y-m-d H:i') }}</div>
                @else
                    <span class="badge bg-label-warning">Unread</span>
                @endif
            </td>
            <td>
                <span class="text-muted small">{{ $notif->created_at->format('Y-m-d H:i') }}</span>
            </td>
            <td class="text-end">
                @include('admin.components.action-buttons', [
                    'type'       => 'delete',
                    'formAction' => route('admin.notifications.destroy', $notif),
                    'confirm'    => 'Are you sure you want to delete this notification log?',
                    'title'      => 'Delete Log',
                ])
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="7" class="text-center py-4 text-muted">
                <i class="mdi mdi-alert-circle-outline d-block mb-2" style="font-size: 24px;"></i>
                No notifications found matching the filters.
            </td>
        </tr>
    @endforelse
@endcomponent
@endsection

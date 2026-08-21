@extends('layouts/contentNavbarLayout')

@section('title', 'Club Memberships')

@section('content')
@component('admin.components.datatable', [
    'title'       => 'Club Memberships',
    'subtitle'    => 'Manage player memberships across clubs',
    'paginator'   => $memberships,
    'createUrl'   => null,
    'search'      => $search,
    'perPage'     => $perPage,
    'sort'        => $sort,
    'direction'   => $direction,
    'filters'     => [
        [
            'name'    => 'status',
            'label'   => 'Status',
            'value'   => $status,
            'options' => [
                ''          => 'All Statuses',
                'approved'  => 'Approved',
                'suspended' => 'Suspended',
                'removed'   => 'Removed',
            ],
        ],
        [
            'name'    => 'club_id',
            'label'   => 'Club',
            'value'   => $clubId,
            'options' => ['' => 'All Clubs'] + $clubs->pluck('club_name', 'id')->toArray(),
        ],
    ],
    'columns' => [
        ['label' => 'Player',            'sortable' => false],
        ['label' => 'Club',              'sortable' => false],
        ['label' => 'Membership Number', 'field' => 'membership_number', 'sortable' => true],
        ['label' => 'Type',              'sortable' => false],
        ['label' => 'Expiry Date',       'sortable' => false],
        ['label' => 'Verification Mode', 'sortable' => false],
        ['label' => 'Status',            'field' => 'status', 'sortable' => true],
        ['label' => 'Approved At',       'field' => 'created_at', 'sortable' => true],
        ['label' => 'Actions',           'actions' => true],
    ],
])
    @forelse($memberships as $membership)
        <tr>
            <td>
                <div class="d-flex align-items-center gap-2">
                    @if($membership->player?->profile_image)
                        <img src="{{ asset('storage/' . $membership->player->profile_image) }}" alt="{{ $membership->player->name }}"
                             class="rounded-circle" style="width:36px;height:36px;object-fit:cover;">
                    @else
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-label-success"
                             style="width:36px;height:36px;flex-shrink:0;">
                            <i class="mdi mdi-account" style="font-size:18px;"></i>
                        </div>
                    @endif
                    <div>
                        <span class="fw-semibold d-block">{{ $membership->player?->name ?? '—' }}</span>
                        <small class="text-muted">{{ $membership->player?->email ?? '—' }}</small>
                    </div>
                </div>
            </td>
            <td>
                <span class="fw-semibold">{{ $membership->club?->club_name ?? $membership->club?->name ?? '—' }}</span>
            </td>
            <td><code>{{ $membership->membership_number }}</code></td>
            <td><span class="text-capitalize">{{ $membership->membership_type ?? 'Permanent' }}</span></td>
            <td>
                @if(($membership->membership_type ?? 'permanent') === 'temporary' && $membership->membership_expiry_date)
                    <span class="text-danger fw-semibold">{{ $membership->membership_expiry_date->format('Y-m-d') }}</span>
                @else
                    <span class="text-muted">—</span>
                @endif
            </td>
            <td><span class="text-capitalize">{{ str_replace('_', ' ', $membership->verification_mode ?? '—') }}</span></td>
            <td>
                @php
                    $statusMap = [
                        'approved'  => ['bg-label-success', 'Approved'],
                        'suspended' => ['bg-label-warning', 'Suspended'],
                        'removed'   => ['bg-label-danger',  'Removed'],
                    ];
                    [$badgeClass, $badgeLabel] = $statusMap[$membership->status] ?? ['bg-label-secondary', ucfirst($membership->status)];
                @endphp
                <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
            </td>
            <td>{{ $membership->approved_at ? $membership->approved_at->format('Y-m-d H:i') : '—' }}</td>
            <td class="text-end">
                @if($membership->status !== 'removed')
                    @include('admin.components.action-buttons', [
                        'type'       => 'delete',
                        'formAction' => route('admin.memberships.destroy', $membership),
                        'confirm'    => "Are you sure you want to remove this player's membership? This will mark it as removed in the system.",
                        'title'      => 'Remove Membership',
                    ])
                @else
                    <span class="text-muted small">No actions</span>
                @endif
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="7" class="text-center py-4 text-muted">
                <i class="mdi mdi-alert-circle-outline d-block mb-2" style="font-size: 24px;"></i>
                No memberships found matching the filters.
            </td>
        </tr>
    @endforelse
@endcomponent
@endsection

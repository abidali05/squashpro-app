@extends('layouts/contentNavbarLayout')

@section('title', 'Club Membership Requests')

@section('content')
@component('admin.components.datatable', [
    'title'       => 'Membership Requests',
    'subtitle'    => 'Track and review player membership requests',
    'paginator'   => $requests,
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
                ''         => 'All Statuses',
                'pending'  => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
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
        ['label' => 'Membership Number', 'sortable' => false],
        ['label' => 'Status',            'field' => 'status', 'sortable' => true],
        ['label' => 'Reviewer',          'sortable' => false],
        ['label' => 'Requested At',      'field' => 'created_at', 'sortable' => true],
    ],
])
    @forelse($requests as $req)
        <tr>
            <td>
                <div class="d-flex align-items-center gap-2">
                    @if($req->player?->profile_image)
                        <img src="{{ asset('storage/' . $req->player->profile_image) }}" alt="{{ $req->player->name }}"
                             class="rounded-circle" style="width:36px;height:36px;object-fit:cover;">
                    @else
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-label-success"
                             style="width:36px;height:36px;flex-shrink:0;">
                            <i class="mdi mdi-account" style="font-size:18px;"></i>
                        </div>
                    @endif
                    <div>
                        <span class="fw-semibold d-block">{{ $req->player?->name ?? '—' }}</span>
                        <small class="text-muted">{{ $req->player?->email ?? '—' }}</small>
                    </div>
                </div>
            </td>
            <td>
                <span class="fw-semibold">{{ $req->club?->club_name ?? $req->club?->name ?? '—' }}</span>
            </td>
            <td><code>{{ $req->membership_number }}</code></td>
            <td>
                @php
                    $statusMap = [
                        'pending'  => ['bg-label-warning', 'Pending'],
                        'approved' => ['bg-label-success', 'Approved'],
                        'rejected' => ['bg-label-danger',  'Rejected'],
                    ];
                    [$badgeClass, $badgeLabel] = $statusMap[$req->status] ?? ['bg-label-secondary', ucfirst($req->status)];
                @endphp
                <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
            </td>
            <td>
                @if($req->reviewer)
                    <div>
                        <span class="d-block small fw-semibold">{{ $req->reviewer->name }}</span>
                        @if($req->reviewed_at)
                            <small class="text-muted text-nowrap">{{ $req->reviewed_at->format('Y-m-d H:i') }}</small>
                        @endif
                    </div>
                @else
                    <span class="text-muted">—</span>
                @endif
            </td>
            <td>{{ $req->created_at->format('Y-m-d H:i') }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="text-center py-4 text-muted">
                <i class="mdi mdi-alert-circle-outline d-block mb-2" style="font-size: 24px;"></i>
                No membership requests found matching the filters.
            </td>
        </tr>
    @endforelse
@endcomponent
@endsection

@extends('layouts/contentNavbarLayout')

@section('title', 'Tournament Registrations')

@section('content')
@component('admin.components.datatable', [
    'title'       => 'Registrations & Teams',
    'subtitle'    => 'Manage player enrollments and team submissions for tournaments',
    'paginator'   => $registrations,
    'createUrl'   => null,
    'search'      => $search,
    'perPage'     => $perPage,
    'sort'        => $sort,
    'direction'   => $direction,
    'filters'     => [
        [
            'name'    => 'registration_status',
            'label'   => 'Reg Status',
            'value'   => $regStatus,
            'options' => [
                ''           => 'All Statuses',
                'registered' => 'Registered',
                'pending'    => 'Pending',
                'cancelled'  => 'Cancelled',
            ],
        ],
        [
            'name'    => 'payment_status',
            'label'   => 'Payment Status',
            'value'   => $payStatus,
            'options' => [
                ''        => 'All Payments',
                'paid'    => 'Paid',
                'pending' => 'Pending',
                'failed'  => 'Failed',
            ],
        ],
        [
            'name'    => 'tournament_id',
            'label'   => 'Tournament',
            'value'   => $tournamentId,
            'options' => ['' => 'All Tournaments'] + $tournaments->pluck('name', 'id')->toArray(),
        ],
    ],
    'columns' => [
        ['label' => 'Player',              'sortable' => false],
        ['label' => 'Tournament',          'sortable' => false],
        ['label' => 'Registration Status', 'field' => 'registration_status', 'sortable' => true],
        ['label' => 'Payment Status',      'field' => 'payment_status', 'sortable' => true],
        ['label' => 'Amount Paid',         'sortable' => false],
        ['label' => 'Enrolled At',         'field' => 'created_at', 'sortable' => true],
        ['label' => 'Actions',             'actions' => true],
    ],
])
    @forelse($registrations as $reg)
        <tr>
            <td>
                <div class="d-flex align-items-center gap-2">
                    @if($reg->player?->profile_image)
                        <img src="{{ asset('storage/' . $reg->player->profile_image) }}" alt="{{ $reg->player->name }}"
                             class="rounded-circle" style="width:36px;height:36px;object-fit:cover;">
                    @else
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-label-success"
                             style="width:36px;height:36px;flex-shrink:0;">
                            <i class="mdi mdi-account" style="font-size:18px;"></i>
                        </div>
                    @endif
                    <div>
                        <span class="fw-semibold d-block">{{ $reg->player?->name ?? '—' }}</span>
                        <small class="text-muted">{{ $reg->player?->email ?? '—' }}</small>
                    </div>
                </div>
            </td>
            <td>
                <span class="fw-semibold">{{ $reg->tournament?->name ?? '—' }}</span>
            </td>
            <td>
                @php
                    $regStatusMap = [
                        'registered' => 'bg-label-success',
                        'pending'    => 'bg-label-warning',
                        'cancelled'  => 'bg-label-danger',
                    ];
                    $regBadge = $regStatusMap[$reg->registration_status] ?? 'bg-label-secondary';
                @endphp
                <span class="badge {{ $regBadge }}">{{ ucfirst($reg->registration_status) }}</span>
            </td>
            <td>
                @php
                    $payStatusMap = [
                        'paid'    => 'bg-success',
                        'pending' => 'bg-warning',
                        'failed'  => 'bg-danger',
                    ];
                    $payDot = $payStatusMap[$reg->payment_status] ?? 'bg-secondary';
                @endphp
                <span class="d-inline-block rounded-circle {{ $payDot }}" style="width: 8px; height: 8px; margin-right: 4px;"></span>
                <span class="text-capitalize">{{ $reg->payment_status }}</span>
            </td>
            <td>{{ $reg->currency }} {{ number_format($reg->amount, 2) }}</td>
            <td>{{ $reg->created_at->format('Y-m-d H:i') }}</td>
            <td class="text-end">
                @if($reg->registration_status !== 'cancelled')
                    <div class="d-flex align-items-center justify-content-end gap-1">
                        @if($reg->registration_status === 'pending')
                            <form action="{{ route('admin.tournament-registrations.approve', $reg) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" title="Accept Registration">
                                    <i class="mdi mdi-check-circle-outline"></i> Accept
                                </button>
                            </form>
                        @endif
                        @include('admin.components.action-buttons', [
                            'type'       => 'delete',
                            'formAction' => route('admin.tournament-registrations.destroy', $reg),
                            'confirm'    => "Are you sure you want to cancel this player's registration? This will mark it as cancelled and reduce the registered count.",
                            'title'      => 'Cancel Registration',
                        ])
                    </div>
                @else
                    <span class="text-muted small">No actions</span>
                @endif
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="7" class="text-center py-4 text-muted">
                <i class="mdi mdi-alert-circle-outline d-block mb-2" style="font-size: 24px;"></i>
                No registrations or teams found matching the filters.
            </td>
        </tr>
    @endforelse
@endcomponent
@endsection

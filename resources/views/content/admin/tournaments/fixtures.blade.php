@extends('layouts/contentNavbarLayout')

@section('title', 'Fixtures Management')

@section('content')
@component('admin.components.datatable', [
    'title'       => 'Fixtures Management',
    'subtitle'    => 'Manage tournament fixtures, group stages, assigned courts, and match details',
    'paginator'   => $fixtures,
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
                'scheduled' => 'Scheduled',
                'bye'       => 'Bye',
                'rest'      => 'Rest',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
            ],
        ],
        [
            'name'    => 'tournament_id',
            'label'   => 'Tournament',
            'value'   => $tournamentId,
            'options' => ['' => 'All Tournaments'] + $tournaments->pluck('name', 'id')->toArray(),
        ],
        [
            'name'    => 'court_id',
            'label'   => 'Court',
            'value'   => $courtId,
            'options' => ['' => 'All Courts'] + $courts->pluck('name', 'id')->toArray(),
        ],
    ],
    'columns' => [
        ['label' => 'Fixture / Round',    'field' => 'round', 'sortable' => true],
        ['label' => 'Tournament',         'sortable' => false],
        ['label' => 'Group / Stage',      'sortable' => false],
        ['label' => 'Matchup',            'sortable' => false],
        ['label' => 'Assigned Court',     'sortable' => false],
        ['label' => 'Status',             'field' => 'status', 'sortable' => true],
        ['label' => 'Matches',            'sortable' => false],
        ['label' => 'Actions',            'actions' => true],
    ],
])
    @forelse($fixtures as $f)
        <tr>
            <td>
                <span class="fw-bold text-primary">{{ $f->round }}</span>
                <small class="d-block text-muted">ID: #{{ $f->id }}</small>
            </td>
            <td>
                <span class="fw-semibold">{{ $f->tournament?->name ?? '—' }}</span>
                @if($f->tournament?->format)
                    <span class="badge bg-label-info ms-1" style="font-size: 10px;">{{ ucfirst($f->tournament->format) }}</span>
                @endif
            </td>
            <td>
                @if($f->group)
                    <span class="badge bg-label-primary"><i class="mdi mdi-account-group me-1"></i>{{ $f->group->name }}</span>
                @else
                    <span class="text-muted small">Knockout Stage</span>
                @endif
            </td>
            <td>
                @if($f->is_bye)
                    <span class="badge bg-label-warning me-1">BYE</span>
                    <span class="fw-semibold">{{ $f->byeClub?->club_name ?? $f->byeClub?->name ?? '—' }}</span>
                @elseif($f->is_rest)
                    <span class="badge bg-label-secondary me-1">REST</span>
                    <span class="fw-semibold">{{ $f->restClub?->club_name ?? $f->restClub?->name ?? '—' }}</span>
                @else
                    <div class="d-flex align-items-center gap-1">
                        <span class="fw-semibold">{{ $f->homeClub?->club_name ?? $f->homeClub?->name ?? ($f->home_placeholder ?: 'TBD') }}</span>
                        <span class="text-muted small px-1">vs</span>
                        <span class="fw-semibold">{{ $f->awayClub?->club_name ?? $f->awayClub?->name ?? ($f->away_placeholder ?: 'TBD') }}</span>
                    </div>
                @endif
            </td>
            <td>
                @php
                    $fixtureCourt = $f->court;
                    $matchCourtIds = $f->matches->pluck('court.name')->filter()->unique();
                    $matchVenueIds = $f->matches->pluck('venue_id')->filter()->unique();
                @endphp
                @if($fixtureCourt)
                    <span class="badge bg-label-success me-1 mb-1">
                        <i class="mdi mdi-map-marker-outline me-1"></i>{{ $fixtureCourt->name }}
                    </span>
                @elseif($matchCourtIds->isNotEmpty())
                    <span class="badge bg-label-success me-1 mb-1">
                        <i class="mdi mdi-map-marker-outline me-1"></i>{{ $matchCourtIds->implode(', ') }}
                    </span>
                @endif

                @if($matchVenueIds->isNotEmpty())
                    <span class="badge bg-label-info mb-1">
                        <i class="mdi mdi-domain me-1"></i>Venue #{{ $matchVenueIds->implode(', #') }}
                    </span>
                @elseif(!$fixtureCourt && $matchCourtIds->isEmpty())
                    <span class="text-muted small">Unassigned</span>
                @endif
            </td>
            <td>
                @php
                    $statusMap = [
                        'scheduled' => 'bg-label-primary',
                        'bye'       => 'bg-label-warning',
                        'rest'      => 'bg-label-secondary',
                        'completed' => 'bg-label-success',
                        'cancelled' => 'bg-label-danger',
                    ];
                    $badgeClass = $statusMap[$f->status] ?? 'bg-label-secondary';
                @endphp
                <span class="badge {{ $badgeClass }}">{{ ucfirst($f->status) }}</span>
            </td>
            <td>
                <span class="badge rounded-pill bg-outline-primary">
                    {{ $f->matches->count() }} {{ Str::plural('Match', $f->matches->count()) }}
                </span>
            </td>
            <td class="text-end">
                <div class="d-flex align-items-center justify-content-end gap-1">
                    <a href="{{ route('admin.fixtures.show', $f) }}" class="btn btn-sm btn-icon btn-outline-info" title="View Fixture Details">
                        <i class="mdi mdi-eye-outline"></i>
                    </a>
                    @include('admin.components.action-buttons', [
                        'type'       => 'delete',
                        'formAction' => route('admin.fixtures.destroy', $f),
                        'confirm'    => 'Are you sure you want to delete this fixture and its associated matches?',
                        'title'      => 'Delete Fixture',
                    ])
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="8" class="text-center py-4 text-muted">
                <i class="mdi mdi-alert-circle-outline d-block mb-2" style="font-size: 24px;"></i>
                No fixtures found matching the selected filters.
            </td>
        </tr>
    @endforelse
@endcomponent
@endsection

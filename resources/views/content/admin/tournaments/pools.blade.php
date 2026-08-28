@extends('layouts/contentNavbarLayout')

@section('title', 'Tournament Pools')

@section('content')
@component('admin.components.datatable', [
    'title'       => 'Tournament Pools',
    'subtitle'    => 'Manage and view group stage pools and club assignments for all tournaments',
    'paginator'   => $pools,
    'createUrl'   => null,
    'search'      => $search,
    'perPage'     => $perPage,
    'sort'        => $sort,
    'direction'   => $direction,
    'filters'     => [
        [
            'name'    => 'format',
            'label'   => 'Format',
            'value'   => $format,
            'options' => [
                ''         => 'All Formats',
                'league'   => 'League',
                'knockout' => 'Knockout',
            ],
        ],
        [
            'name'    => 'tournament_id',
            'label'   => 'Tournament',
            'value'   => $tournamentId,
            'options' => ['' => 'All Tournaments'] + $tournaments->pluck('name', 'id')->toArray(),
        ],
        [
            'name'    => 'club_id',
            'label'   => 'Club',
            'value'   => $clubId,
            'options' => ['' => 'All Clubs'] + $clubs->pluck('club_name', 'id')->toArray(),
        ],
    ],
    'columns' => [
        ['label' => 'Tournament',         'sortable' => false],
        ['label' => 'Club',               'sortable' => false],
        ['label' => 'Format',             'field' => 'format', 'sortable' => true],
        ['label' => 'Pool Status',        'sortable' => false],
        ['label' => 'Configured Pools',   'sortable' => false],
        ['label' => 'Created At',         'field' => 'created_at', 'sortable' => true],
        ['label' => 'Actions',            'actions' => true],
    ],
])
    @forelse($pools as $pool)
        <tr>
            <td>
                <span class="fw-bold text-primary">{{ $pool->tournament?->name ?? '—' }}</span>
                <small class="d-block text-muted">ID: #{{ $pool->tournament_id }}</small>
            </td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    @if($pool->club?->club_logo)
                        <img src="{{ asset('storage/' . $pool->club->club_logo) }}" alt="{{ $pool->club->club_name }}"
                             class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">
                    @else
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-label-primary"
                             style="width:32px;height:32px;flex-shrink:0;">
                            <i class="mdi mdi-domain" style="font-size:16px;"></i>
                        </div>
                    @endif
                    <div>
                        <span class="fw-semibold d-block">{{ $pool->club?->club_name ?? $pool->club?->name ?? '—' }}</span>
                    </div>
                </div>
            </td>
            <td>
                @php
                    $fmt = strtolower($pool->format ?? '');
                    $fmtBadge = $fmt === 'league' ? 'bg-label-info' : ($fmt === 'knockout' ? 'bg-label-warning' : 'bg-label-primary');
                @endphp
                <span class="badge {{ $fmtBadge }}">{{ ucfirst($pool->format ?: 'Standard') }}</span>
            </td>
            <td>
                @if($pool->has_pools)
                    <span class="badge bg-label-success"><i class="mdi mdi-check-circle me-1"></i>Has Pools</span>
                @else
                    <span class="badge bg-label-secondary"><i class="mdi mdi-minus-circle me-1"></i>No Pools</span>
                @endif
            </td>
            <td>
                @if(is_array($pool->pools) && count($pool->pools) > 0)
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($pool->pools as $p)
                            <span class="badge bg-label-primary">
                                {{ $p['pool_name'] ?? ('Pool ' . ($p['pool_index'] ?? $loop->iteration)) }}
                                @if(!empty($p['club_ids']) && is_array($p['club_ids']))
                                    ({{ count($p['club_ids']) }} Clubs)
                                @endif
                            </span>
                        @endforeach
                    </div>
                @else
                    <span class="text-muted small">No Pools Created</span>
                @endif
            </td>
            <td>{{ $pool->created_at ? $pool->created_at->format('Y-m-d H:i') : '—' }}</td>
            <td class="text-end">
                <div class="d-flex align-items-center justify-content-end gap-1">
                    <a href="{{ route('admin.tournament-pools.show', $pool) }}" class="btn btn-sm btn-icon btn-outline-info" title="View Pool Details">
                        <i class="mdi mdi-eye-outline"></i>
                    </a>
                    @include('admin.components.action-buttons', [
                        'type'       => 'delete',
                        'formAction' => route('admin.tournament-pools.destroy', $pool),
                        'confirm'    => 'Are you sure you want to delete these tournament pools?',
                        'title'      => 'Delete Pools',
                    ])
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="7" class="text-center py-4 text-muted">
                <i class="mdi mdi-alert-circle-outline d-block mb-2" style="font-size: 24px;"></i>
                No tournament pools found matching the selected filters.
            </td>
        </tr>
    @endforelse
@endcomponent
@endsection

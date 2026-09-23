@extends('layouts/contentNavbarLayout')

@section('title', 'Tournament Rules')

@section('content')
@component('admin.components.datatable', [
    'title'       => 'Tournament Rules',
    'subtitle'    => 'Manage and view format, pool rules, knockout rules, equipment, and scoring rules for all tournaments',
    'paginator'   => $rules,
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
        ['label' => 'Format',             'field' => 'tournament_format', 'sortable' => true],
        ['label' => 'Scoring / Equipment','sortable' => false],
        ['label' => 'Configured Rules',   'sortable' => false],
        ['label' => 'Created At',         'field' => 'created_at', 'sortable' => true],
        ['label' => 'Actions',            'actions' => true],
    ],
])
    @forelse($rules as $rule)
        <tr>
            <td>
                <span class="fw-bold text-primary">{{ $rule->tournament?->name ?? '—' }}</span>
                <small class="d-block text-muted">ID: #{{ $rule->tournament_id }}</small>
            </td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    @if($rule->club?->club_logo)
                        <img src="{{ asset('storage/' . $rule->club->club_logo) }}" alt="{{ $rule->club->club_name }}"
                             class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">
                    @else
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-label-primary"
                             style="width:32px;height:32px;flex-shrink:0;">
                            <i class="mdi mdi-domain" style="font-size:16px;"></i>
                        </div>
                    @endif
                    <div>
                        <span class="fw-semibold d-block">{{ $rule->club?->club_name ?? $rule->club?->name ?? '—' }}</span>
                    </div>
                </div>
            </td>
            <td>
                @php
                    $fmt = strtolower($rule->tournament_format ?? '');
                    $fmtBadge = $fmt === 'league' ? 'bg-label-info' : ($fmt === 'knockout' ? 'bg-label-warning' : 'bg-label-primary');
                @endphp
                <span class="badge {{ $fmtBadge }}">{{ ucfirst($rule->tournament_format ?: 'Standard') }}</span>
            </td>
            <td>
                <div class="small">
                    @if($rule->scoring_rules)
                        <div><i class="mdi mdi-scoreboard-outline text-success me-1"></i>Scoring Configured</div>
                    @endif
                    @if($rule->match_equipment)
                        <div><i class="mdi mdi-tennis text-info me-1"></i>Equipment Configured</div>
                    @endif
                    @if(!$rule->scoring_rules && !$rule->match_equipment)
                        <span class="text-muted">—</span>
                    @endif
                </div>
            </td>
            <td>
                <div class="d-flex flex-wrap gap-1">
                    @if($rule->competition_setup)
                        <span class="badge bg-label-secondary">Setup</span>
                    @endif
                    @if($rule->pool_rules)
                        <span class="badge bg-label-secondary">Pool Rules</span>
                    @endif
                    @if($rule->knockout_rounds)
                        <span class="badge bg-label-secondary">Knockout</span>
                    @endif
                    @if(!$rule->competition_setup && !$rule->pool_rules && !$rule->knockout_rounds)
                        <span class="text-muted small">Standard Rules</span>
                    @endif
                </div>
            </td>
            <td>{{ $rule->created_at ? $rule->created_at->format('Y-m-d H:i') : '—' }}</td>
            <td class="text-end">
                <div class="d-flex align-items-center justify-content-end gap-1">
                    <a href="{{ route('admin.tournament-rules.show', $rule) }}" class="btn btn-sm btn-icon btn-outline-info" title="View Rule Details">
                        <i class="mdi mdi-eye-outline"></i>
                    </a>
                    @include('admin.components.action-buttons', [
                        'type'       => 'delete',
                        'formAction' => route('admin.tournament-rules.destroy', $rule),
                        'confirm'    => 'Are you sure you want to delete these tournament rules?',
                        'title'      => 'Delete Rules',
                    ])
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="7" class="text-center py-4 text-muted">
                <i class="mdi mdi-alert-circle-outline d-block mb-2" style="font-size: 24px;"></i>
                No tournament rules found matching the selected filters.
            </td>
        </tr>
    @endforelse
@endcomponent
@endsection

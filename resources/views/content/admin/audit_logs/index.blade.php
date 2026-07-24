@extends('layouts/contentNavbarLayout')

@section('title', 'Activity Logs')

@section('content')
@component('admin.components.datatable', [
    'title'       => 'Activity Logs',
    'subtitle'    => 'Track system updates, user decisions, and audit history',
    'paginator'   => $logs,
    'createUrl'   => null,
    'search'      => $search,
    'perPage'     => $perPage,
    'filters'     => [
        [
            'name'    => 'action_filter',
            'label'   => 'Action Type',
            'value'   => $actionFilter,
            'options' => ['' => 'All Actions'] + array_combine($actionsList, array_map(fn($a) => ucfirst(str_replace('_', ' ', $a)), $actionsList)),
        ],
    ],
    'columns' => [
        ['label' => 'Timestamp',   'sortable' => false],
        ['label' => 'Actor',       'sortable' => false],
        ['label' => 'Action Type', 'sortable' => false],
        ['label' => 'Entity',      'sortable' => false],
        ['label' => 'Changes',     'sortable' => false],
    ],
])
    @forelse($logs as $log)
        <tr>
            <td>
                <span class="text-muted small">{{ $log->created_at->format('Y-m-d H:i:s') }}</span>
            </td>
            <td>
                @if($log->actor)
                    <div>
                        <span class="fw-semibold d-block">{{ $log->actor->name }}</span>
                        <span class="badge bg-label-secondary text-uppercase" style="font-size:10px;">{{ $log->actor->role }}</span>
                    </div>
                @else
                    <span class="text-muted">System / Guest</span>
                @endif
            </td>
            <td>
                <span class="badge bg-label-info text-capitalize">{{ str_replace('_', ' ', $log->action) }}</span>
            </td>
            <td>
                @if($log->entity_type)
                    @php
                        $entityName = class_basename($log->entity_type);
                    @endphp
                    <code>{{ $entityName }} #{{ $log->entity_id }}</code>
                @else
                    <span class="text-muted">—</span>
                @endif
            </td>
            <td>
                @if($log->before || $log->after)
                    <button type="button" class="btn btn-xs btn-outline-dark" data-bs-toggle="collapse" data-bs-target="#collapse-log-{{ $log->id }}">
                        View details
                    </button>
                    <div class="collapse mt-2" id="collapse-log-{{ $log->id }}">
                        <div class="bg-light p-2 rounded small text-start border" style="max-height: 250px; overflow-y: auto;">
                            @if($log->before)
                                <div class="mb-1 text-danger fw-bold">Before:</div>
                                <pre class="m-0 p-0 text-muted" style="font-size:11px;">{{ json_encode($log->before, JSON_PRETTY_PRINT) }}</pre>
                            @endif
                            @if($log->after)
                                <div class="mt-2 mb-1 text-success fw-bold">After:</div>
                                <pre class="m-0 p-0 text-muted" style="font-size:11px;">{{ json_encode($log->after, JSON_PRETTY_PRINT) }}</pre>
                            @endif
                        </div>
                    </div>
                @else
                    <span class="text-muted">No attributes changed</span>
                @endif
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class="text-center py-4 text-muted">
                <i class="mdi mdi-alert-circle-outline d-block mb-2" style="font-size: 24px;"></i>
                No activity logs found matching the filters.
            </td>
        </tr>
    @endforelse
@endcomponent
@endsection

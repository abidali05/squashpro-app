@php
    $title = $title ?? '';
    $subtitle = $subtitle ?? null;
    $paginator = $paginator ?? null;
    $createUrl = $createUrl ?? null;
    $createLabel = $createLabel ?? 'Create';
    $search = $search ?? '';
    $perPage = $perPage ?? 10;
    $sort = $sort ?? 'created_at';
    $direction = $direction ?? 'desc';
    $columns = $columns ?? [];
    $filters = $filters ?? [];  // extra dropdown filters e.g. status
    $query = request()->query();

    $filterId = 'admin-filters-' . \Illuminate\Support\Str::slug($title ?: 'table') . '-' . uniqid();

    // Active if search OR any extra filter has a value
    $hasActiveFilters = filled($search);
    foreach ($filters as $f) {
        if (filled($f['value'] ?? '')) { $hasActiveFilters = true; break; }
    }
@endphp

<div class="admin-table-card card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="mb-1">{{ $title }}</h5>
            @if($subtitle)
                <p class="text-muted mb-0 small">{{ $subtitle }}</p>
            @endif
        </div>

        @if($createUrl)
            <a href="{{ $createUrl }}" class="btn btn-sm btn-dark admin-add-btn">
                <i class="mdi mdi-plus"></i>
                <span>{{ $createLabel }}</span>
            </a>
        @endif
    </div>

    <div class="card-body">
        <div class="admin-toolbar-top d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="admin-toolbar-left d-flex align-items-center gap-2">
                <form method="GET" class="d-inline-flex align-items-center gap-2 admin-length-form">
                    @foreach(request()->query() as $key => $value)
                        @if(!in_array($key, ['per_page', 'page'], true))
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <span class="admin-length-label">{{ $title }} / page</span>
                    <select name="per_page" class="form-select form-select-sm admin-per-page" onchange="this.form.submit()">
                        @foreach([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) $perPage === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="admin-toolbar-actions d-flex flex-wrap align-items-center gap-2">
                <button
                    class="btn btn-sm admin-filter-toggle {{ $hasActiveFilters ? 'is-active' : '' }}"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#{{ $filterId }}"
                    aria-expanded="{{ $hasActiveFilters ? 'true' : 'false' }}"
                    aria-controls="{{ $filterId }}"
                >
                    <i class="mdi mdi-tune-variant"></i>
                    <span>Filters</span>
                </button>
            </div>
        </div>

        <div id="{{ $filterId }}" class="collapse admin-filter-collapse {{ $hasActiveFilters ? 'show' : '' }} mb-3">
            <div class="admin-filter-panel">
                <form method="GET" class="row g-2 align-items-center">
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    <input type="hidden" name="direction" value="{{ $direction }}">
                    <input type="hidden" name="per_page" value="{{ $perPage }}">

                    <div class="col-12 col-md-8 col-lg-{{ $filters ? '6' : '9' }}">
                        <div class="input-group">
                            <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                            <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Search records...">
                        </div>
                    </div>

                    @foreach($filters as $filter)
                    <div class="col-6 col-md-3 col-lg-2">
                        <select name="{{ $filter['name'] }}" class="form-select">
                            @foreach($filter['options'] as $val => $label)
                                <option value="{{ $val }}" @selected(($filter['value'] ?? '') == $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endforeach

                    <div class="col-6 col-md-2 col-lg-1">
                        <button class="btn btn-outline-dark w-100" type="submit">Apply</button>
                    </div>

                    <div class="col-6 col-md-2 col-lg-2">
                        <a class="btn btn-outline-secondary w-100" href="{{ request()->url() }}">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-responsive admin-table-wrap">
            <table class="table admin-datatable">
                <thead>
                    <tr>
                        @foreach($columns as $column)
                            @php
                                $label = $column['label'];
                                $field = $column['field'] ?? null;
                                $isSortable = (bool) ($column['sortable'] ?? false);
                                $isAction = (bool) ($column['actions'] ?? false);
                                $nextDirection = ($sort === $field && $direction === 'asc') ? 'desc' : 'asc';
                                $sortQuery = array_merge($query, ['sort' => $field, 'direction' => $nextDirection]);
                            @endphp
                            <th class="{{ $isAction ? 'text-end admin-actions-col' : '' }}">
                                @if($isSortable && $field)
                                    <a class="admin-sort-link" href="{{ request()->url() . '?' . http_build_query($sortQuery) }}">
                                        <span>{{ $label }}</span>
                                        @if($sort === $field)
                                            <i class="mdi {{ $direction === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down' }}"></i>
                                        @else
                                            <i class="mdi mdi-swap-vertical"></i>
                                        @endif
                                    </a>
                                @else
                                    {{ $label }}
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    {{ $slot }}
                </tbody>
            </table>
        </div>

        @if($paginator)
            @include('admin.components.pagination', ['paginator' => $paginator])
        @endif
    </div>
</div>

@once
    @push('my-script')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                    new bootstrap.Tooltip(el);
                });

                document.querySelectorAll('.admin-filter-toggle').forEach(function (button) {
                    var target = document.querySelector(button.getAttribute('data-bs-target'));
                    if (!target) return;

                    target.addEventListener('shown.bs.collapse', function () {
                        button.classList.add('is-active');
                    });

                    target.addEventListener('hidden.bs.collapse', function () {
                        button.classList.remove('is-active');
                    });
                });
            });
        </script>
    @endpush
@endonce

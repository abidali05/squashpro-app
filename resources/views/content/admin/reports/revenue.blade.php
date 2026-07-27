@extends('layouts/contentNavbarLayout')

@section('title', 'Revenue Reports')

@section('content')
<div class="revenue-reports-page">
    
    {{-- Page Header --}}
    <div class="app-page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="app-page-header__title mb-1">Revenue Reports</h4>
            <p class="app-page-header__subtitle mb-0 text-muted">Platform performance and earnings breakdown</p>
        </div>
        <div>
            <form method="GET" class="d-flex align-items-center gap-2">
                <label class="mb-0 text-nowrap fw-semibold">Select Year:</label>
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    @for($y = \Carbon\Carbon::now()->year; $y >= 2024; $y--)
                        <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                    @endfor
                </select>
            </form>
        </div>
    </div>

    {{-- Metric Cards --}}
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm bg-primary text-white">
                <div class="card-body d-flex align-items-center">
                    <div class="badge bg-white text-primary p-3 me-3 rounded">
                        <i class="mdi mdi-cash-multiple fs-3"></i>
                    </div>
                    <div>
                        <h6 class="card-title text-white opacity-75 mb-1">Total Revenue (All Time)</h6>
                        <h4 class="mb-0 fw-bold">PKR {{ number_format($totalRevenue, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="badge bg-label-success p-3 me-3 rounded">
                        <i class="mdi mdi-calendar-check fs-3"></i>
                    </div>
                    <div>
                        <h6 class="card-title text-muted mb-1">Court Booking Revenue</h6>
                        <h4 class="mb-0 fw-bold text-success">PKR {{ number_format($bookingRevenue, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="badge bg-label-warning p-3 me-3 rounded">
                        <i class="mdi mdi-trophy fs-3"></i>
                    </div>
                    <div>
                        <h6 class="card-title text-muted mb-1">Tournament Entry Revenue</h6>
                        <h4 class="mb-0 fw-bold text-warning">PKR {{ number_format($tournamentRevenue, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Monthly Breakdown Table & Visual Bar Chart representation --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header border-bottom py-3">
            <h5 class="mb-0 fw-semibold">Monthly Breakdown ({{ $year }})</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Month</th>
                        <th>Court Bookings</th>
                        <th>Tournament Entries</th>
                        <th>Total Revenue</th>
                        <th style="width: 30%;">Visual Share</th>
                    </tr>
                </thead>
                <tbody>
                    @php($maxTotal = collect($monthlyChartData)->max('total') ?: 1)
                    @foreach($monthlyChartData as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['month'] }}</td>
                            <td>PKR {{ number_format($row['bookings'], 2) }}</td>
                            <td>PKR {{ number_format($row['tournaments'], 2) }}</td>
                            <td class="fw-bold text-dark">PKR {{ number_format($row['total'], 2) }}</td>
                            <td>
                                @php($pct = ($row['total'] / $maxTotal) * 100)
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-success" style="width: {{ $pct }}%" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Payment Methods Split --}}
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-bottom py-3">
                    <h5 class="mb-0 fw-semibold">Court Booking Payment Methods</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @forelse($bookingMethods as $method)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <span class="text-uppercase fw-semibold text-dark">{{ str_replace('_', ' ', $method['method']) }}</span>
                                    <small class="text-muted d-block">{{ $method['count'] }} bookings</small>
                                </div>
                                <span class="badge bg-label-success rounded-pill fw-semibold">PKR {{ number_format($method['revenue'], 2) }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted py-3 px-0">No booking payments recorded.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-bottom py-3">
                    <h5 class="mb-0 fw-semibold">Tournament Entry Payment Methods</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @forelse($tournamentMethods as $method)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <span class="text-uppercase fw-semibold text-dark">{{ str_replace('_', ' ', $method['method']) }}</span>
                                    <small class="text-muted d-block">{{ $method['count'] }} registrations</small>
                                </div>
                                <span class="badge bg-label-warning rounded-pill fw-semibold">PKR {{ number_format($method['revenue'], 2) }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted py-3 px-0">No tournament payments recorded.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

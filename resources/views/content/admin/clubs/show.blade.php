@extends('layouts/contentNavbarLayout')

@section('title', $club->club_name . ' — Club Detail')

@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.clubs.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="mdi mdi-arrow-left"></i> Back
    </a>
    <h5 class="mb-0">Club Detail</h5>
</div>

<div class="row g-4">

    {{-- Club Info Card --}}
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-body text-center pt-4">
                @if($club->club_logo)
                    <img src="{{ asset('storage/' . $club->club_logo) }}" alt="{{ $club->club_name }}" class="rounded mb-3" style="width:80px;height:80px;object-fit:cover;">
                @else
                    <div class="rounded d-flex align-items-center justify-content-center bg-label-primary mx-auto mb-3" style="width:80px;height:80px;">
                        <i class="mdi mdi-domain" style="font-size:36px;"></i>
                    </div>
                @endif
                <h5 class="mb-1">{{ $club->club_name }}</h5>
                <p class="text-muted small mb-3">{{ $club->email }}</p>

                @php
                    $statusMap = [
                        'active'      => ['bg-label-success', 'Active'],
                        'pending'     => ['bg-label-warning', 'Pending Approval'],
                        'otp_pending' => ['bg-label-secondary', 'OTP Pending'],
                        'rejected'    => ['bg-label-danger', 'Rejected'],
                        'suspended'   => ['bg-label-dark', 'Suspended'],
                    ];
                    [$badgeClass, $badgeLabel] = $statusMap[$club->status] ?? ['bg-label-secondary', ucfirst($club->status)];
                @endphp
                <span class="badge {{ $badgeClass }} mb-3">{{ $badgeLabel }}</span>

                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <a href="{{ route('admin.clubs.edit', $club) }}" class="btn btn-sm btn-dark">
                        <i class="mdi mdi-pencil-outline"></i> Edit
                    </a>
                    @include('admin.components.action-buttons', [
                        'type' => 'delete',
                        'formAction' => route('admin.clubs.destroy', $club),
                        'confirm' => "Delete club \"{$club->club_name}\"? All courts will also be removed.",
                    ])
                </div>
            </div>
        </div>
    </div>

    {{-- Details --}}
    <div class="col-12 col-lg-8">
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0">Club Information</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6"><p class="text-muted small mb-1">Owner / Manager</p><p class="fw-semibold mb-0">{{ $club->name }}</p></div>
                    <div class="col-6"><p class="text-muted small mb-1">Phone</p><p class="fw-semibold mb-0">{{ $club->phone ?? '—' }}</p></div>
                    <div class="col-6"><p class="text-muted small mb-1">City</p><p class="fw-semibold mb-0">{{ $club->city ?? '—' }}</p></div>
                    <div class="col-6"><p class="text-muted small mb-1">Address</p><p class="fw-semibold mb-0">{{ $club->address ?? '—' }}</p></div>
                    <div class="col-6"><p class="text-muted small mb-1">Number of Courts</p><p class="fw-semibold mb-0">{{ $club->number_of_courts ?? '—' }}</p></div>
                    <div class="col-6"><p class="text-muted small mb-1">Working Hours</p><p class="fw-semibold mb-0">{{ $club->working_hours ?? '—' }}</p></div>
                    <div class="col-6"><p class="text-muted small mb-1">OTP Verified</p><p class="fw-semibold mb-0">{{ $club->otp_verified ? 'Yes' : 'No' }}</p></div>
                    <div class="col-6"><p class="text-muted small mb-1">Registered</p><p class="fw-semibold mb-0">{{ $club->created_at->format('d M Y') }}</p></div>
                </div>
            </div>
        </div>

        {{-- Update Status --}}
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0">Update Status</h6></div>
            <div class="card-body">
                <form action="{{ route('admin.clubs.status', $club) }}" method="POST" class="d-flex align-items-center gap-3 flex-wrap">
                    @csrf @method('PATCH')
                    <select name="status" class="form-select form-select-sm" style="max-width:220px;">
                        @foreach(['otp_pending' => 'OTP Pending', 'pending' => 'Pending Approval', 'active' => 'Active', 'rejected' => 'Rejected', 'suspended' => 'Suspended'] as $val => $label)
                            <option value="{{ $val }}" @selected($club->status === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-dark">Update Status</button>
                </form>
            </div>
        </div>

        {{-- Tabs for Courts, Schedules, Members, and Requests --}}
        <div class="nav-align-top mb-4">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-courts" aria-controls="navs-top-courts" aria-selected="true">
                        <i class="mdi mdi-tennis-ball me-1"></i> Courts ({{ $club->courts->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-schedules" aria-controls="navs-top-schedules" aria-selected="false">
                        <i class="mdi mdi-calendar-clock me-1"></i> Schedules
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-members" aria-controls="navs-top-members" aria-selected="false">
                        <i class="mdi mdi-account-group-outline me-1"></i> Members ({{ $club->clubMemberships->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-requests" aria-controls="navs-top-requests" aria-selected="false">
                        <i class="mdi mdi-account-question-outline me-1"></i> Requests ({{ $club->clubMembershipRequests->count() }})
                    </button>
                </li>
            </ul>
            <div class="tab-content p-0 bg-white">
                {{-- Courts Tab --}}
                <div class="tab-pane fade show active" id="navs-top-courts" role="tabpanel">
                    <div class="card-header d-flex justify-content-between align-items-center px-4 py-3">
                        <h6 class="mb-0">Courts ({{ $club->courts->count() }})</h6>
                        <a href="{{ route('admin.clubs.courts.create', $club) }}" class="btn btn-sm btn-dark">
                            <i class="mdi mdi-plus"></i> Add Court
                        </a>
                    </div>
                    <div class="card-body p-0">
                        @if($club->courts->isEmpty())
                            <p class="text-muted text-center py-4 mb-0">No courts added yet.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table admin-datatable mb-0">
                                    <thead>
                                        <tr>
                                            <th>Court Name</th>
                                            <th>Type</th>
                                            <th>Price/Hour</th>
                                            <th>Status</th>
                                            <th class="text-end admin-actions-col">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($club->courts as $court)
                                            <tr>
                                                <td class="fw-semibold">{{ $court->name }}</td>
                                                <td>{{ ucfirst($court->type ?? '—') }}</td>
                                                <td>${{ number_format($court->price_per_hour, 2) }}</td>
                                                <td>
                                                    @if($court->status === 'active')
                                                        <span class="badge bg-label-success">Active</span>
                                                    @elseif($court->status === 'maintenance')
                                                        <span class="badge bg-label-warning">Maintenance</span>
                                                    @else
                                                        <span class="badge bg-label-secondary">Inactive</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @include('admin.components.action-buttons', [
                                                        'type' => 'edit',
                                                        'href' => route('admin.clubs.courts.edit', [$club, $court]),
                                                        'title' => 'Edit Court',
                                                    ])
                                                    @include('admin.components.action-buttons', [
                                                        'type' => 'delete',
                                                        'formAction' => route('admin.clubs.courts.destroy', [$club, $court]),
                                                        'confirm' => 'Delete this court?',
                                                    ])
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Schedules Tab --}}
                <div class="tab-pane fade" id="navs-top-schedules" role="tabpanel">
                    <div class="p-4">
                        <div class="row g-4">
                            {{-- Working Hours --}}
                            <div class="col-12 col-md-6">
                                <h6 class="pb-2 border-bottom"><i class="mdi mdi-clock-check-outline me-1 text-primary"></i> Working Hours</h6>
                                @if($club->workingHoursSchedule->isEmpty())
                                    <div class="alert alert-light py-2 small mb-0">
                                        <i class="mdi mdi-information-outline me-1"></i> No custom working hours set. Inherits default: <code>{{ $club->working_hours ?? '—' }}</code>
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Day</th>
                                                    <th>Status</th>
                                                    <th>Hours</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($club->workingHoursSchedule as $wh)
                                                    <tr>
                                                        <td class="text-capitalize fw-semibold">{{ $wh->day }}</td>
                                                        <td>
                                                            <span class="badge {{ $wh->is_open ? 'bg-label-success' : 'bg-label-danger' }}">
                                                                {{ $wh->is_open ? 'Open' : 'Closed' }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            @if($wh->is_open)
                                                                <code>{{ \Carbon\Carbon::parse($wh->opens_at)->format('g:i A') }} - {{ \Carbon\Carbon::parse($wh->closes_at)->format('g:i A') }}</code>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>

                            {{-- Non-Member Windows --}}
                            <div class="col-12 col-md-6">
                                <h6 class="pb-2 border-bottom"><i class="mdi mdi-account-circle-outline me-1 text-info"></i> Non-Member Booking Windows</h6>
                                @if(!$club->non_member_booking_allowed)
                                    <div class="alert alert-secondary py-2 small mb-0">
                                        <i class="mdi mdi-information-outline me-1"></i> Non-member court booking is disabled for this club.
                                    </div>
                                @elseif($club->nonMemberWindows->isEmpty())
                                    <div class="alert alert-light py-2 small mb-0">
                                        <i class="mdi mdi-information-outline me-1"></i> No custom windows defined. Inherits global settings.
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Day</th>
                                                    <th>Availability</th>
                                                    <th>Available Time Slots</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($club->nonMemberWindows as $nmw)
                                                    <tr>
                                                        <td class="text-capitalize fw-semibold">{{ $nmw->day }}</td>
                                                        <td>
                                                            <span class="badge {{ $nmw->is_available ? 'bg-label-info' : 'bg-label-secondary' }}">
                                                                {{ $nmw->is_available ? 'Allowed' : 'Restricted' }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            @if($nmw->is_available)
                                                                <code>{{ \Carbon\Carbon::parse($nmw->from_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($nmw->to_time)->format('g:i A') }}</code>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Members Tab --}}
                <div class="tab-pane fade" id="navs-top-members" role="tabpanel">
                    <div class="card-header px-4 py-3"><h6 class="mb-0">Club Members ({{ $club->clubMemberships->count() }})</h6></div>
                    <div class="card-body p-0">
                        @if($club->clubMemberships->isEmpty())
                            <p class="text-muted text-center py-4 mb-0">No active members in this club.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Player</th>
                                            <th>Membership Number</th>
                                            <th>Type</th>
                                            <th>Expiry Date</th>
                                            <th>Status</th>
                                            <th>Approved At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($club->clubMemberships as $membership)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        @if($membership->player?->profile_image)
                                                            <img src="{{ asset('storage/' . $membership->player->profile_image) }}" alt="{{ $membership->player->name }}" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                                                        @else
                                                            <div class="rounded-circle d-flex align-items-center justify-content-center bg-label-success" style="width: 32px; height: 32px; font-size: 14px;">
                                                                <i class="mdi mdi-account"></i>
                                                            </div>
                                                        @endif
                                                        <div>
                                                            <span class="fw-semibold d-block small">{{ $membership->player?->name ?? '—' }}</span>
                                                            <small class="text-muted">{{ $membership->player?->email ?? '—' }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><code>{{ $membership->membership_number }}</code></td>
                                                <td><span class="text-capitalize small">{{ $membership->membership_type ?? 'Permanent' }}</span></td>
                                                <td>
                                                    @if(($membership->membership_type ?? 'permanent') === 'temporary' && $membership->membership_expiry_date)
                                                        <span class="text-danger fw-semibold small">{{ $membership->membership_expiry_date->format('Y-m-d') }}</span>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        $stMap = [
                                                            'approved'  => 'bg-label-success',
                                                            'suspended' => 'bg-label-warning',
                                                            'removed'   => 'bg-label-danger',
                                                        ];
                                                        $badge = $stMap[$membership->status] ?? 'bg-label-secondary';
                                                    @endphp
                                                    <span class="badge {{ $badge }}">{{ ucfirst($membership->status) }}</span>
                                                </td>
                                                <td><span class="text-muted small">{{ $membership->approved_at?->format('Y-m-d') ?? '—' }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Requests Tab --}}
                <div class="tab-pane fade" id="navs-top-requests" role="tabpanel">
                    <div class="card-header px-4 py-3"><h6 class="mb-0">Club Joining Requests ({{ $club->clubMembershipRequests->count() }})</h6></div>
                    <div class="card-body p-0">
                        @if($club->clubMembershipRequests->isEmpty())
                            <p class="text-muted text-center py-4 mb-0">No membership requests found.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Player</th>
                                            <th>Verification Mode</th>
                                            <th>Requested Type</th>
                                            <th>Expiry (If Temp)</th>
                                            <th>Status</th>
                                            <th>Requested At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($club->clubMembershipRequests as $requestRecord)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        @if($requestRecord->player?->profile_image)
                                                            <img src="{{ asset('storage/' . $requestRecord->player->profile_image) }}" alt="{{ $requestRecord->player->name }}" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                                                        @else
                                                            <div class="rounded-circle d-flex align-items-center justify-content-center bg-label-primary" style="width: 32px; height: 32px; font-size: 14px;">
                                                                <i class="mdi mdi-account"></i>
                                                            </div>
                                                        @endif
                                                        <div>
                                                            <span class="fw-semibold d-block small">{{ $requestRecord->player?->name ?? '—' }}</span>
                                                            <small class="text-muted">{{ $requestRecord->player?->email ?? '—' }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class="text-capitalize small">{{ str_replace('_', ' ', $requestRecord->verification_mode ?? '—') }}</span></td>
                                                <td><span class="text-capitalize small">{{ $requestRecord->membership_type ?? 'Permanent' }}</span></td>
                                                <td>
                                                    @if(($requestRecord->membership_type ?? 'permanent') === 'temporary' && $requestRecord->membership_expiry_date)
                                                        <span class="text-danger fw-semibold small">{{ $requestRecord->membership_expiry_date->format('Y-m-d') }}</span>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        $reqMap = [
                                                            'approved' => 'bg-label-success',
                                                            'pending'  => 'bg-label-warning',
                                                            'rejected' => 'bg-label-danger',
                                                        ];
                                                        $reqBadge = $reqMap[$requestRecord->status] ?? 'bg-label-secondary';
                                                    @endphp
                                                    <span class="badge {{ $reqBadge }}">{{ ucfirst($requestRecord->status) }}</span>
                                                </td>
                                                <td><span class="text-muted small">{{ $requestRecord->created_at?->format('Y-m-d H:i') ?? '—' }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

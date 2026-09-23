@extends('layouts/contentNavbarLayout')

@section('title', 'Tournament Detail')

@section('content')
<div class="admin-page">
    <div class="admin-page-header">
        <div class="admin-page-header__left">
            <h4 class="admin-page-header__title">{{ $tournament->name }}</h4>
            <p class="admin-page-header__subtitle">{{ $tournament->club?->club_name ?? '—' }} · {{ \Illuminate\Support\Str::headline((string) $tournament->format) }}</p>
        </div>
        <div class="admin-page-header__actions d-flex align-items-center gap-2">
            <a href="{{ route('admin.tournaments.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="mdi mdi-arrow-left me-1"></i> Back
            </a>
            <a href="{{ route('admin.tournaments.rules', $tournament) }}" class="btn btn-outline-info btn-sm">
                <i class="mdi mdi-cogs me-1"></i> Rule View
            </a>
            <a href="{{ route('admin.tournaments.pools', $tournament) }}" class="btn btn-outline-primary btn-sm">
                <i class="mdi mdi-format-list-checks me-1"></i> Pool View
            </a>
            <a href="{{ route('admin.tournaments.fixtures', $tournament) }}" class="btn btn-outline-warning btn-sm">
                <i class="mdi mdi-tournament me-1"></i> Fixture View
            </a>
            @php
                $statusMap = [
                    'open' => ['bg-label-success', 'Open'],
                    'full' => ['bg-label-warning', 'Full'],
                    'closed' => ['bg-label-secondary', 'Closed'],
                    'completed' => ['bg-label-primary', 'Completed'],
                    'cancelled' => ['bg-label-danger', 'Cancelled'],
                ];
                [$badgeClass, $badgeLabel] = $statusMap[$tournament->status] ?? ['bg-label-secondary', ucfirst($tournament->status)];
            @endphp
            <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0">Tournament Snapshot</h6></div>
                <div class="card-body text-center">
                    @if($tournament->tournament_image)
                        <img src="{{ str_starts_with($tournament->tournament_image, 'http') ? $tournament->tournament_image : asset('storage/' . $tournament->tournament_image) }}" alt="{{ $tournament->name }}" class="tournament-cover mb-3">
                    @else
                        <div class="tournament-cover tournament-cover--fallback mb-3"><i class="mdi mdi-trophy-outline"></i></div>
                    @endif
                    <h5 class="mb-1">{{ $tournament->name }}</h5>
                    <p class="text-muted small mb-3">Tournament #{{ $tournament->id }}</p>
                    <div class="detail-stack detail-stack--single text-start">
                        <div><span class="text-muted small d-block">Start Date</span><strong>{{ $tournament->start_date?->format('Y-m-d') }}</strong></div>
                        <div><span class="text-muted small d-block">End Date</span><strong>{{ $tournament->end_date?->format('Y-m-d') }}</strong></div>
                        <div><span class="text-muted small d-block">Registration Deadline</span><strong>{{ $tournament->registration_deadline?->format('Y-m-d') }}</strong></div>
                        <div><span class="text-muted small d-block">Players</span><strong>{{ (int) $tournament->registered_players_count }}/{{ (int) $tournament->allowed_player }}</strong></div>
                        <div><span class="text-muted small d-block">Entry Fees</span><strong>PKR {{ number_format((float) $tournament->entry_fees, 0) }}</strong></div>
                        <div><span class="text-muted small d-block">Prize Pool</span><strong>PKR {{ number_format((float) $tournament->prize_pool, 0) }}</strong></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h6 class="mb-0">Club Information</h6></div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        @if($tournament->club?->club_logo)
                            <img src="{{ asset('storage/' . $tournament->club->club_logo) }}" alt="{{ $tournament->club?->club_name }}" class="tournament-club-logo">
                        @else
                            <div class="tournament-club-logo tournament-club-logo--fallback"><i class="mdi mdi-domain"></i></div>
                        @endif
                        <div>
                            <h6 class="mb-1">{{ $tournament->club?->club_name ?? '—' }}</h6>
                            <p class="mb-0 text-muted small">{{ $tournament->club?->city ?? '—' }}</p>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4"><p class="text-muted small mb-1">Manager</p><p class="fw-semibold mb-0">{{ $tournament->club?->name ?? '—' }}</p></div>
                        <div class="col-md-4"><p class="text-muted small mb-1">Email</p><p class="fw-semibold mb-0">{{ $tournament->club?->email ?? '—' }}</p></div>
                        <div class="col-md-4"><p class="text-muted small mb-1">Phone</p><p class="fw-semibold mb-0">{{ $tournament->club?->phone ?? '—' }}</p></div>
                        <div class="col-12"><p class="text-muted small mb-1">Address</p><p class="fw-semibold mb-0">{{ $tournament->club?->address ?? '—' }}</p></div>
                    </div>
                </div>
            </div>

            @if($tournament->tournament_type === 'CLUB_TO_CLUB')
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Opponent Club Invitations</h6>
                        <span class="badge bg-secondary">{{ $tournament->invitations->count() }} Invited</span>
                    </div>
                    <div class="card-body">
                        @if($tournament->invitations->isEmpty())
                            <div class="text-center py-3 text-muted">
                                <i class="mdi mdi-email-open-outline d-block mb-1" style="font-size: 24px;"></i>
                                No club invitations sent.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Opponent Club</th>
                                            <th>City</th>
                                            <th>Invitation Status</th>
                                            <th>Invited At</th>
                                            <th>Responded At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tournament->invitations as $invite)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        @if($invite->invitedClub?->club_logo)
                                                            <img src="{{ asset('storage/' . $invite->invitedClub->club_logo) }}" alt="{{ $invite->invitedClub->club_name }}" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                                                        @else
                                                            <div class="rounded-circle bg-label-info d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 14px;">
                                                                <i class="mdi mdi-domain"></i>
                                                            </div>
                                                        @endif
                                                        <div>
                                                            <span class="fw-semibold d-block">{{ $invite->invitedClub?->club_name ?? $invite->invitedClub?->name ?? '—' }}</span>
                                                            <small class="text-muted">{{ $invite->invitedClub?->email ?? '—' }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $invite->invitedClub?->city ?? '—' }}</td>
                                                <td>
                                                    @php
                                                        $invStatusMap = [
                                                            'accepted' => 'bg-label-success',
                                                            'pending' => 'bg-label-warning',
                                                            'rejected' => 'bg-label-danger',
                                                        ];
                                                        $invBadge = $invStatusMap[$invite->status] ?? 'bg-label-secondary';
                                                    @endphp
                                                    <span class="badge {{ $invBadge }}">{{ ucfirst($invite->status) }}</span>
                                                </td>
                                                <td><span class="text-muted small">{{ $invite->invited_at?->format('Y-m-d H:i') ?? '—' }}</span></td>
                                                <td><span class="text-muted small">{{ $invite->responded_at?->format('Y-m-d H:i') ?? '—' }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if($tournament->tournament_type === 'CLUB_TO_CLUB')
                <div class="card mb-4">
                    <div class="card-header"><h6 class="mb-0">Assigned Match Officials</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <p class="text-muted small mb-2 fw-semibold">Scorers</p>
                                @if($tournament->scorers->isEmpty())
                                    <p class="text-muted small">No scorers assigned.</p>
                                @else
                                    <ul class="list-unstyled mb-0">
                                        @foreach($tournament->scorers as $scorer)
                                            <li class="mb-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    @if($scorer->profile_image)
                                                        <img src="{{ asset('storage/' . $scorer->profile_image) }}" alt="{{ $scorer->name }}" class="rounded-circle" style="width: 28px; height: 28px; object-fit: cover;">
                                                    @else
                                                        <div class="rounded-circle bg-label-secondary d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 12px;">
                                                            <i class="mdi mdi-account"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <span class="fw-semibold small d-block">{{ $scorer->name }}</span>
                                                        <small class="text-muted" style="font-size: 11px;">{{ $scorer->email }}</small>
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <p class="text-muted small mb-2 fw-semibold">Umpires</p>
                                @if($tournament->umpires->isEmpty())
                                    <p class="text-muted small">No umpires assigned.</p>
                                @else
                                    <ul class="list-unstyled mb-0">
                                        @foreach($tournament->umpires as $umpire)
                                            <li class="mb-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    @if($umpire->profile_image)
                                                        <img src="{{ asset('storage/' . $umpire->profile_image) }}" alt="{{ $umpire->name }}" class="rounded-circle" style="width: 28px; height: 28px; object-fit: cover;">
                                                    @else
                                                        <div class="rounded-circle bg-label-secondary d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 12px;">
                                                            <i class="mdi mdi-account"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <span class="fw-semibold small d-block">{{ $umpire->name }}</span>
                                                        <small class="text-muted" style="font-size: 11px;">{{ $umpire->email }}</small>
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card mb-4">
                <div class="card-header"><h6 class="mb-0">Rules</h6></div>
                <div class="card-body">
                    <p class="mb-0">{{ $tournament->rules ?? 'No rules added.' }}</p>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Registrations & Teams</h6>
                    <span class="badge bg-primary">{{ $tournament->registrations->count() }} Registered</span>
                </div>
                <div class="card-body">
                    @if($tournament->registrations->isEmpty())
                        <div class="text-center py-3 text-muted">
                            <i class="mdi mdi-account-multiple-outline d-block mb-1" style="font-size: 24px;"></i>
                            No player registrations or teams submitted yet.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Player</th>
                                        <th>Gender</th>
                                        <th>Level</th>
                                        <th>Registration Status</th>
                                        <th>Payment</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tournament->registrations as $reg)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div>
                                                        <span class="fw-semibold d-block">{{ $reg->player?->name ?? '—' }}</span>
                                                        <small class="text-muted">{{ $reg->player?->email ?? '—' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="text-capitalize">{{ $reg->player?->gender ?? '—' }}</span></td>
                                            <td><span class="text-capitalize">{{ $reg->player?->playing_level ?? '—' }}</span></td>
                                            <td>
                                                @php
                                                    $regStatusMap = [
                                                        'registered' => 'bg-label-success',
                                                        'pending' => 'bg-label-warning',
                                                        'cancelled' => 'bg-label-danger',
                                                    ];
                                                    $regBadge = $regStatusMap[$reg->registration_status] ?? 'bg-label-secondary';
                                                @endphp
                                                <span class="badge {{ $regBadge }}">{{ ucfirst($reg->registration_status) }}</span>
                                            </td>
                                            <td>
                                                @php
                                                    $payStatusMap = [
                                                        'paid' => 'bg-success',
                                                        'pending' => 'bg-warning',
                                                        'failed' => 'bg-danger',
                                                    ];
                                                    $payDot = $payStatusMap[$reg->payment_status] ?? 'bg-secondary';
                                                @endphp
                                                <span class="d-inline-block rounded-circle {{ $payDot }}" style="width: 8px; height: 8px; margin-right: 4px;"></span>
                                                <span class="text-capitalize">{{ $reg->payment_status }}</span>
                                            </td>
                                            <td>{{ $reg->currency }} {{ number_format($reg->amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h6 class="mb-0">Status</h6></div>
                <div class="card-body">
                    <form action="{{ route('admin.tournaments.status', $tournament) }}" method="POST" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-md-8">
                            <label class="form-label">Update Tournament Status</label>
                            <select name="status" class="form-select">
                                @foreach(['pending' => 'Pending Invitation', 'soft_accepted' => 'Soft Accepted', 'confirmed' => 'Confirmed Team', 'rejected' => 'Rejected Invitation', 'open' => 'Open', 'full' => 'Full', 'closed' => 'Closed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $val => $label)
                                    <option value="{{ $val }}" @selected($tournament->status === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-dark w-100" type="submit">
                                <i class="mdi mdi-content-save-outline me-1"></i> Save Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('my-styles')
<style>
    .detail-stack { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
    .detail-stack--single { grid-template-columns: 1fr; }
    .tournament-cover {
        width: 100%;
        max-width: 320px;
        height: 180px;
        border-radius: 16px;
        object-fit: cover;
        border: 1px solid #e6ebf2;
    }
    .tournament-cover--fallback {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eef2ff;
        color: #4f46e5;
        font-size: 42px;
    }
    .tournament-club-logo,
    .tournament-club-logo--fallback {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        object-fit: cover;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #ecfdf5;
        color: #059669;
        font-size: 28px;
    }
    @media (max-width: 767px) { .detail-stack { grid-template-columns: 1fr; } }
</style>
@endpush

@extends('layouts/contentNavbarLayout')

@section('title', 'Tournament Pool Details')

@section('content')
@php
    $tourn = $tournamentPool?->tournament ?? $tournament ?? null;
    $club = $tournamentPool?->club ?? $tourn?->club ?? null;
@endphp

<div class="admin-page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div class="admin-page-header__left">
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold mb-0 text-dark">
                {{ $tourn?->name ?? 'Tournament Pools' }}
            </h4>
            <span class="badge bg-label-primary px-2.5 py-1 rounded-pill ms-2">
                {{ ucfirst(($tournamentPool?->format ?: $tourn?->format) ?: 'Standard') }}
            </span>
        </div>
        <p class="text-muted small mb-0">
            Host Club: <strong class="text-dark">{{ $club?->club_name ?? $club?->name ?? '—' }}</strong>
        </p>
    </div>
    <div class="admin-page-header__actions ms-auto">
        <a href="{{ route('admin.tournaments.index') }}" class="btn btn-outline-secondary btn-sm shadow-xs">
            <i class="mdi mdi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <!-- Overview & Basic Details -->
    <div class="col-md-4 mb-4">
        <div class="card h-100 border shadow-xs">
            <div class="card-header bg-light border-bottom py-3">
                <h5 class="card-title mb-0 fw-bold fs-6">
                    <i class="mdi mdi-format-list-checks text-purple me-2" style="color: #7e22ce;"></i>Pool Overview
                </h5>
            </div>
            <div class="card-body pt-3">
                <div class="detail-row py-2 border-bottom d-flex align-items-center justify-content-between">
                    <span class="text-muted small"><i class="mdi mdi-trophy-outline me-1"></i>Tournament:</span>
                    <span class="fw-semibold text-end">
                        @if($tourn)
                            <a href="{{ route('admin.tournaments.show', $tourn) }}" class="text-primary">{{ $tourn->name }}</a>
                        @else
                            —
                        @endif
                    </span>
                </div>
                <div class="detail-row py-2 border-bottom d-flex align-items-center justify-content-between">
                    <span class="text-muted small"><i class="mdi mdi-shape-outline me-1"></i>Format:</span>
                    <span class="badge bg-label-info">{{ ucfirst(($tournamentPool?->format ?: $tourn?->format) ?: 'Standard') }}</span>
                </div>
                <div class="detail-row py-2 border-bottom d-flex align-items-center justify-content-between">
                    <span class="text-muted small"><i class="mdi mdi-domain me-1"></i>Host Club:</span>
                    <span class="fw-semibold text-end">{{ $club?->club_name ?? $club?->name ?? '—' }}</span>
                </div>
                <div class="detail-row py-2 border-bottom d-flex align-items-center justify-content-between">
                    <span class="text-muted small"><i class="mdi mdi-check-circle-outline me-1"></i>Pool Status:</span>
                    <span>
                        @if($tournamentPool?->has_pools)
                            <span class="badge bg-label-success px-2 py-1"><i class="mdi mdi-check-circle me-1"></i>Has Pools</span>
                        @else
                            <span class="badge bg-label-secondary px-2 py-1"><i class="mdi mdi-minus-circle me-1"></i>No Pools</span>
                        @endif
                    </span>
                </div>
                <div class="detail-row py-2 border-bottom d-flex align-items-center justify-content-between">
                    <span class="text-muted small"><i class="mdi mdi-counter me-1"></i>Total Pools:</span>
                    <span class="fw-bold text-dark fs-6">{{ ($tournamentPool && is_array($tournamentPool->pools)) ? count($tournamentPool->pools) : 0 }}</span>
                </div>
                <div class="detail-row py-2 border-bottom d-flex align-items-center justify-content-between">
                    <span class="text-muted small"><i class="mdi mdi-calendar-clock me-1"></i>Configured Date:</span>
                    <span class="text-dark small">{{ $tournamentPool?->created_at ? $tournamentPool->created_at->format('Y-m-d H:i') : '—' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Structured Pools Section -->
    <div class="col-md-8 mb-4">
        @if($tournamentPool && is_array($tournamentPool->pools) && count($tournamentPool->pools) > 0)
            <div class="row g-3">
                @foreach($tournamentPool->pools as $pIndex => $p)
                    <div class="col-md-6">
                        <div class="card h-100 border shadow-xs">
                            <div class="card-header bg-label-primary d-flex align-items-center justify-content-between py-2.5">
                                <h6 class="card-title mb-0 fw-bold text-primary">
                                    <i class="mdi mdi-format-list-bulleted-type me-1"></i>
                                    {{ $p['pool_name'] ?? ('Pool ' . ($p['pool_index'] ?? ($loop->iteration))) }}
                                </h6>
                                <span class="badge bg-primary">Index #{{ $p['pool_index'] ?? $loop->iteration }}</span>
                            </div>
                            <div class="card-body pt-3">
                                <small class="text-uppercase text-muted fw-bold d-block mb-2" style="font-size: 10.5px; letter-spacing: 0.5px;">Assigned Clubs / Teams</small>
                                
                                @if(!empty($p['club_ids']) && is_array($p['club_ids']))
                                    <div class="list-group list-group-flush rounded border">
                                        @foreach($p['club_ids'] as $cid)
                                            @php
                                                $assignedClub = isset($referencedClubs) ? $referencedClubs->get((int)$cid) : null;
                                            @endphp
                                            <div class="list-group-item px-3 py-2 d-flex align-items-center gap-2">
                                                @if($assignedClub?->club_logo)
                                                    <img src="{{ asset('storage/' . $assignedClub->club_logo) }}" alt="{{ $assignedClub->club_name }}"
                                                         class="rounded-circle" style="width:28px;height:28px;object-fit:cover;">
                                                @else
                                                    <div class="rounded-circle bg-label-secondary d-flex align-items-center justify-content-center"
                                                         style="width:28px;height:28px;font-size:12px;">
                                                        <i class="mdi mdi-domain"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <span class="fw-semibold small d-block text-dark">{{ $assignedClub?->club_name ?? $assignedClub?->name ?? ('Club #' . $cid) }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-muted small py-2">No clubs assigned to this pool.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="card h-100 border shadow-xs">
                <div class="card-body text-center py-5">
                    <div class="mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center"
                         style="width:72px; height:72px; background: linear-gradient(135deg, #F3E8FF 0%, #E9D5FF 100%); border: 1px solid #D8B4FE;">
                        <i class="mdi mdi-format-list-checks text-purple" style="font-size: 32px; color: #7e22ce;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">No Pools Configured</h5>
                    <p class="text-muted mb-0 px-md-5" style="max-width: 480px; margin: 0 auto;">
                        The host club has not configured group stage pools for <strong>{{ $tourn?->name ?? 'this tournament' }}</strong> yet.
                    </p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

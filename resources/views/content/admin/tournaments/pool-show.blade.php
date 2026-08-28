@extends('layouts/contentNavbarLayout')

@section('title', 'Tournament Pool Details')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <a href="{{ route('admin.tournament-pools.index') }}" class="text-muted fw-normal me-2">
                <i class="mdi mdi-arrow-left"></i> Tournament Pools /
            </a>
            Pools for {{ $tournamentPool->tournament?->name ?? ('Tournament #' . $tournamentPool->tournament_id) }}
        </h4>
        <span class="text-muted">Host Club: <strong>{{ $tournamentPool->club?->club_name ?? $tournamentPool->club?->name ?? '—' }}</strong></span>
    </div>
    <div>
        <a href="{{ route('admin.tournament-pools.index') }}" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left me-1"></i> Back to Pools
        </a>
    </div>
</div>

<div class="row">
    <!-- Overview & Basic Details -->
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Pool Overview</h5>
            </div>
            <div class="card-body pt-3">
                <div class="row mb-2">
                    <div class="col-5 text-muted">Tournament:</div>
                    <div class="col-7 fw-semibold">
                        @if($tournamentPool->tournament)
                            <a href="{{ route('admin.tournaments.show', $tournamentPool->tournament) }}">{{ $tournamentPool->tournament->name }}</a>
                        @else
                            #{{ $tournamentPool->tournament_id }}
                        @endif
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted">Format:</div>
                    <div class="col-7">
                        <span class="badge bg-label-primary">{{ ucfirst($tournamentPool->format ?: 'Standard') }}</span>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted">Club:</div>
                    <div class="col-7 fw-semibold">{{ $tournamentPool->club?->club_name ?? $tournamentPool->club?->name ?? '—' }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted">Pools Status:</div>
                    <div class="col-7">
                        @if($tournamentPool->has_pools)
                            <span class="badge bg-label-success"><i class="mdi mdi-check-circle me-1"></i>Has Pools</span>
                        @else
                            <span class="badge bg-label-secondary"><i class="mdi mdi-minus-circle me-1"></i>No Pools</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted">Total Pools:</div>
                    <div class="col-7 fw-bold">{{ is_array($tournamentPool->pools) ? count($tournamentPool->pools) : 0 }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted">Created At:</div>
                    <div class="col-7">{{ $tournamentPool->created_at ? $tournamentPool->created_at->format('Y-m-d H:i') : '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Structured Pools Section -->
    <div class="col-md-8 mb-4">
        @if(is_array($tournamentPool->pools) && count($tournamentPool->pools) > 0)
            <div class="row g-3">
                @foreach($tournamentPool->pools as $pIndex => $p)
                    <div class="col-md-6">
                        <div class="card h-100 border">
                            <div class="card-header bg-label-primary d-flex align-items-center justify-content-between py-2.5">
                                <h6 class="card-title mb-0 fw-bold text-primary">
                                    <i class="mdi mdi-format-list-bulleted-type me-1"></i>
                                    {{ $p['pool_name'] ?? ('Pool ' . ($p['pool_index'] ?? ($loop->iteration))) }}
                                </h6>
                                <span class="badge bg-primary">Index #{{ $p['pool_index'] ?? $loop->iteration }}</span>
                            </div>
                            <div class="card-body pt-3">
                                <small class="text-uppercase text-muted fw-bold d-block mb-2" style="font-size: 11px; letter-spacing: 0.5px;">Assigned Clubs / Teams</small>
                                
                                @if(!empty($p['club_ids']) && is_array($p['club_ids']))
                                    <div class="list-group list-group-flush rounded border">
                                        @foreach($p['club_ids'] as $cid)
                                            @php
                                                $assignedClub = $referencedClubs->get((int)$cid);
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
                                                    <span class="fw-semibold small d-block">{{ $assignedClub?->club_name ?? $assignedClub?->name ?? ('Club #' . $cid) }}</span>
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
            <div class="card">
                <div class="card-body text-center py-5 text-muted">
                    <i class="mdi mdi-format-list-checks d-block mb-2" style="font-size: 32px;"></i>
                    No pools configured for this tournament yet.
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

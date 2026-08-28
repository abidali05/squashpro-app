@extends('layouts/contentNavbarLayout')

@section('title', 'Tournament Fixture Details')

@section('content')
@php
    $tourn = $fixture?->tournament ?? $tournament ?? null;
    $club = $tourn?->club ?? null;
    $fixtureList = $fixtures ?? ($fixture ? collect([$fixture]) : collect());
@endphp

<div class="admin-page-header mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h4 class="fw-bold mb-0 text-dark">
                    {{ $tourn?->name ?? 'Match Fixtures' }}
                </h4>
                <span class="badge bg-label-primary px-2.5 py-1 rounded-pill ms-2">
                    {{ ucfirst(($tourn?->format) ?: 'Standard') }}
                </span>
            </div>
            <p class="text-muted small mb-0">
                Host Club: <strong class="text-dark">{{ $club?->club_name ?? $club?->name ?? '—' }}</strong>
            </p>
        </div>
        <div>
            <a href="{{ route('admin.tournaments.index') }}" class="btn btn-outline-secondary btn-sm shadow-xs">
                <i class="mdi mdi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>
</div>

@if($fixtureList->isNotEmpty())
    @foreach($fixtureList as $fItem)
        <div class="mb-4">
            <div class="row mb-3">
                <!-- Overview Card -->
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="card h-100 border shadow-xs">
                        <div class="card-header border-bottom bg-light py-3">
                            <h5 class="card-title mb-0 fw-bold fs-6 text-primary">
                                <i class="mdi mdi-trophy-outline me-2"></i>Fixture #{{ $fItem->id }} Overview
                            </h5>
                        </div>
                        <div class="card-body pt-3">
                            <div class="row mb-2">
                                <div class="col-4 text-muted">Tournament:</div>
                                <div class="col-8 fw-semibold">
                                    @if($tourn)
                                        <a href="{{ route('admin.tournaments.show', $tourn) }}" class="text-primary">{{ $tourn->name }}</a>
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4 text-muted">Group / Stage:</div>
                                <div class="col-8">
                                    @if($fItem->group)
                                        <span class="badge bg-label-primary">{{ $fItem->group->name }}</span>
                                    @else
                                        <span class="text-muted">Knockout Stage</span>
                                    @endif
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4 text-muted">Round:</div>
                                <div class="col-8 fw-semibold">{{ $fItem->round }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4 text-muted">Fixture Status:</div>
                                <div class="col-8">
                                    @php
                                        $statusMap = [
                                            'scheduled' => 'bg-label-primary',
                                            'bye'       => 'bg-label-warning',
                                            'rest'      => 'bg-label-secondary',
                                            'completed' => 'bg-label-success',
                                            'cancelled' => 'bg-label-danger',
                                        ];
                                        $badgeClass = $statusMap[$fItem->status] ?? 'bg-label-secondary';
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ ucfirst($fItem->status) }}</span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4 text-muted">Assigned Court:</div>
                                <div class="col-8">
                                    @if($fItem->court)
                                        <span class="badge bg-label-success">
                                            <i class="mdi mdi-map-marker-outline me-1"></i>{{ $fItem->court->name }} ({{ ucfirst($fItem->court->type) }})
                                        </span>
                                    @else
                                        <span class="text-muted">Unassigned at fixture level</span>
                                    @endif
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4 text-muted">Created At:</div>
                                <div class="col-8">{{ $fItem->created_at ? $fItem->created_at->format('Y-m-d H:i') : '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Matchup Card -->
                <div class="col-md-6">
                    <div class="card h-100 border shadow-xs">
                        <div class="card-header border-bottom bg-light py-3">
                            <h5 class="card-title mb-0 fw-bold fs-6">Matchup Details</h5>
                        </div>
                        <div class="card-body pt-3 d-flex flex-column justify-content-center">
                            @if($fItem->is_bye)
                                <div class="text-center py-3">
                                    <span class="badge bg-warning p-2 mb-2">BYE FIXTURE</span>
                                    <h5 class="fw-bold mb-1">{{ $fItem->byeClub?->club_name ?? $fItem->byeClub?->name ?? '—' }}</h5>
                                    <small class="text-muted">This club receives an automatic bye in this round.</small>
                                </div>
                            @elseif($fItem->is_rest)
                                <div class="text-center py-3">
                                    <span class="badge bg-secondary p-2 mb-2">REST FIXTURE</span>
                                    <h5 class="fw-bold mb-1">{{ $fItem->restClub?->club_name ?? $fItem->restClub?->name ?? '—' }}</h5>
                                    <small class="text-muted">This club rests in this round.</small>
                                </div>
                            @else
                                <div class="d-flex align-items-center justify-content-around text-center py-2">
                                    <div class="px-2">
                                        <div class="avatar avatar-md mx-auto mb-2 bg-label-primary rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                                            <i class="mdi mdi-domain fs-3"></i>
                                        </div>
                                        <h6 class="fw-bold mb-0 text-dark">{{ $fItem->homeClub?->club_name ?? $fItem->homeClub?->name ?? ($fItem->home_placeholder ?: 'TBD') }}</h6>
                                        <span class="badge bg-label-primary mt-1">Home Club</span>
                                    </div>
                                    <div class="px-2">
                                        <span class="badge rounded-pill bg-light text-dark fs-6 fw-bold px-3 py-2 border">VS</span>
                                    </div>
                                    <div class="px-2">
                                        <div class="avatar avatar-md mx-auto mb-2 bg-label-danger rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                                            <i class="mdi mdi-domain fs-3"></i>
                                        </div>
                                        <h6 class="fw-bold mb-0 text-dark">{{ $fItem->awayClub?->club_name ?? $fItem->awayClub?->name ?? ($fItem->away_placeholder ?: 'TBD') }}</h6>
                                        <span class="badge bg-label-danger mt-1">Away Club</span>
                                    </div>
                                </div>
                                @if($fItem->winnerClub)
                                    <div class="alert alert-success text-center mt-3 mb-0 p-2 border-0">
                                        <i class="mdi mdi-trophy me-1"></i> Winner: <strong>{{ $fItem->winnerClub->club_name ?? $fItem->winnerClub->name }}</strong>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Associated Matches List -->
            <div class="card border shadow-xs">
                <div class="card-header bg-light py-3 d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0 fw-bold fs-6">
                        <i class="mdi mdi-format-list-bulleted me-2 text-primary"></i>Matches for Fixture #{{ $fItem->id }} ({{ $fItem->matches->count() }})
                    </h6>
                </div>
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Seq #</th>
                                <th>Home Player</th>
                                <th>Away Player</th>
                                <th>Court / Venue</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Score / Winner</th>
                                <th>Officials</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($fItem->matches as $m)
                                <tr>
                                    <td><span class="fw-bold">#{{ $m->sequence }}</span></td>
                                    <td>
                                        @if($m->homePlayer)
                                            <span class="fw-semibold text-dark">{{ $m->homePlayer->name }}</span>
                                        @else
                                            <span class="text-muted">{{ $m->home_player_placeholder ?: 'TBD' }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($m->awayPlayer)
                                            <span class="fw-semibold text-dark">{{ $m->awayPlayer->name }}</span>
                                        @else
                                            <span class="text-muted">{{ $m->away_player_placeholder ?: 'TBD' }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($m->court)
                                            <span class="badge bg-label-success">
                                                <i class="mdi mdi-map-marker-outline me-1"></i>{{ $m->court->name }}
                                            </span>
                                        @elseif($m->venue_id)
                                            <span class="badge bg-label-info">Venue ID: #{{ $m->venue_id }}</span>
                                        @else
                                            <span class="text-muted small">Unassigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($m->start_date || $m->start_time)
                                            <span>{{ $m->start_date ? (is_string($m->start_date) ? $m->start_date : $m->start_date->format('Y-m-d')) : '' }} {{ $m->start_time }}</span>
                                        @else
                                            <span class="text-muted small">TBD</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-label-primary">{{ ucfirst($m->status) }}</span>
                                    </td>
                                    <td>
                                        @if($m->score)
                                            <span class="fw-bold text-dark">{{ $m->score }}</span>
                                        @endif
                                        @if($m->winnerPlayer)
                                            <small class="d-block text-success fw-semibold"><i class="mdi mdi-trophy-outline me-1"></i>{{ $m->winnerPlayer->name }}</small>
                                        @elseif(!$m->score)
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($m->scorers->isNotEmpty())
                                            <small class="d-block">Scorer: {{ $m->scorers->pluck('name')->implode(', ') }}</small>
                                        @endif
                                        @if($m->umpires->isNotEmpty())
                                            <small class="d-block text-muted">Umpire: {{ $m->umpires->pluck('name')->implode(', ') }}</small>
                                        @endif
                                        @if($m->scorers->isEmpty() && $m->umpires->isEmpty())
                                            <span class="text-muted small">None</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-3 text-muted">No individual matches scheduled for this fixture.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
@else
    <div class="card border shadow-xs">
        <div class="card-body text-center py-5">
            <div class="mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center"
                 style="width:72px; height:72px; background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); border: 1px solid #FCD34D;">
                <i class="mdi mdi-tournament text-warning" style="font-size: 32px; color: #d97706;"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">No Fixtures Generated</h5>
            <p class="text-muted mb-0 px-md-5" style="max-width: 480px; margin: 0 auto;">
                No match fixtures or schedules have been generated for <strong>{{ $tourn?->name ?? 'this tournament' }}</strong> yet.
            </p>
        </div>
    </div>
@endif
@endsection

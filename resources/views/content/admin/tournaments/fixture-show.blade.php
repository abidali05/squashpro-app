@extends('layouts/contentNavbarLayout')

@section('title', 'Fixture Details #' . $fixture->id)

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <a href="{{ route('admin.fixtures.index') }}" class="text-muted fw-normal me-2">
                <i class="mdi mdi-arrow-left"></i> Fixtures /
            </a>
            Fixture #{{ $fixture->id }} Details
        </h4>
        <span class="text-muted">Round: <strong>{{ $fixture->round }}</strong></span>
    </div>
    <div>
        <a href="{{ route('admin.fixtures.index') }}" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left me-1"></i> Back to Fixtures
        </a>
    </div>
</div>

<div class="row mb-4">
    <!-- Overview Card -->
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Fixture Overview</h5>
            </div>
            <div class="card-body pt-3">
                <div class="row mb-2">
                    <div class="col-4 text-muted">Tournament:</div>
                    <div class="col-8 fw-semibold">
                        @if($fixture->tournament)
                            <a href="{{ route('admin.tournaments.show', $fixture->tournament) }}">{{ $fixture->tournament->name }}</a>
                        @else
                            —
                        @endif
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Group / Stage:</div>
                    <div class="col-8">
                        @if($fixture->group)
                            <span class="badge bg-label-primary">{{ $fixture->group->name }}</span>
                        @else
                            <span class="text-muted">Knockout Stage</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Round:</div>
                    <div class="col-8 fw-semibold">{{ $fixture->round }}</div>
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
                            $badgeClass = $statusMap[$fixture->status] ?? 'bg-label-secondary';
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ ucfirst($fixture->status) }}</span>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Assigned Court:</div>
                    <div class="col-8">
                        @if($fixture->court)
                            <span class="badge bg-label-success">
                                <i class="mdi mdi-map-marker-outline me-1"></i>{{ $fixture->court->name }} ({{ ucfirst($fixture->court->type) }})
                            </span>
                        @else
                            <span class="text-muted">Unassigned at fixture level</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Created At:</div>
                    <div class="col-8">{{ $fixture->created_at->format('Y-m-d H:i') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Matchup Card -->
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Matchup Details</h5>
            </div>
            <div class="card-body pt-3 d-flex flex-column justify-content-center">
                @if($fixture->is_bye)
                    <div class="text-center py-3">
                        <span class="badge bg-warning p-2 mb-2">BYE FIXTURE</span>
                        <h5 class="fw-bold mb-1">{{ $fixture->byeClub?->club_name ?? $fixture->byeClub?->name ?? '—' }}</h5>
                        <small class="text-muted">This club receives an automatic bye in this round.</small>
                    </div>
                @elseif($fixture->is_rest)
                    <div class="text-center py-3">
                        <span class="badge bg-secondary p-2 mb-2">REST FIXTURE</span>
                        <h5 class="fw-bold mb-1">{{ $fixture->restClub?->club_name ?? $fixture->restClub?->name ?? '—' }}</h5>
                        <small class="text-muted">This club rests in this round.</small>
                    </div>
                @else
                    <div class="d-flex align-items-center justify-content-around text-center py-2">
                        <div class="px-2">
                            <div class="avatar avatar-md mx-auto mb-2 bg-label-primary rounded-circle">
                                <i class="mdi mdi-domain fs-3"></i>
                            </div>
                            <h6 class="fw-bold mb-0">{{ $fixture->homeClub?->club_name ?? $fixture->homeClub?->name ?? ($fixture->home_placeholder ?: 'TBD') }}</h6>
                            <span class="badge bg-label-primary mt-1">Home Club</span>
                        </div>
                        <div class="px-2">
                            <span class="badge rounded-pill bg-light text-dark fs-5 fw-bold px-3 py-2">VS</span>
                        </div>
                        <div class="px-2">
                            <div class="avatar avatar-md mx-auto mb-2 bg-label-danger rounded-circle">
                                <i class="mdi mdi-domain fs-3"></i>
                            </div>
                            <h6 class="fw-bold mb-0">{{ $fixture->awayClub?->club_name ?? $fixture->awayClub?->name ?? ($fixture->away_placeholder ?: 'TBD') }}</h6>
                            <span class="badge bg-label-danger mt-1">Away Club</span>
                        </div>
                    </div>
                    @if($fixture->winnerClub)
                        <div class="alert alert-success text-center mt-3 mb-0 p-2">
                            <i class="mdi mdi-trophy me-1"></i> Winner: <strong>{{ $fixture->winnerClub->club_name ?? $fixture->winnerClub->name }}</strong>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Associated Matches List -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">
            <i class="mdi mdi-format-list-bulleted me-2"></i>Fixture Matches ({{ $fixture->matches->count() }})
        </h5>
    </div>
    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
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
                @forelse($fixture->matches as $m)
                    <tr>
                        <td><span class="fw-bold">#{{ $m->sequence }}</span></td>
                        <td>
                            @if($m->homePlayer)
                                <span class="fw-semibold">{{ $m->homePlayer->name }}</span>
                            @else
                                <span class="text-muted">{{ $m->home_player_placeholder ?: 'TBD' }}</span>
                            @endif
                        </td>
                        <td>
                            @if($m->awayPlayer)
                                <span class="fw-semibold">{{ $m->awayPlayer->name }}</span>
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
@endsection

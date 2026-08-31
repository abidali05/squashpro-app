@extends('layouts/contentNavbarLayout')

@section('title', 'Tournament Fixture Details')

@section('content')
@php
    $tourn = $fixture?->tournament ?? $tournament ?? null;
    $club = $tourn?->club ?? null;
    $fixtureList = $fixtures ?? ($fixture ? collect([$fixture]) : collect());
@endphp

<style>
    .fixture-page-title {
        color: #000000 !important;
        font-weight: 700;
    }
    .fixture-page-link {
        color: #000000 !important;
        font-weight: 700;
        text-decoration: underline;
    }
    .fixture-page-link:hover {
        color: #333333 !important;
    }
    .fixture-card-custom {
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03) !important;
        overflow: hidden;
        background-color: #ffffff;
    }
    .fixture-card-header-custom {
        background-color: #f8fafc !important;
        border-bottom: 1px solid #e2e8f0 !important;
        padding: 14px 20px !important;
    }
    .fixture-info-row {
        padding: 8px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .fixture-info-row:last-child {
        border-bottom: none;
    }
</style>

<!-- Page Header -->
<div class="admin-page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div class="admin-page-header__left">
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold mb-0 text-dark">
                {{ $tourn?->name ?? 'Match Fixtures' }}
            </h4>
            <span class="badge bg-label-primary px-3 py-1 rounded-pill ms-2 fw-semibold">
                {{ ucfirst(($tourn?->format) ?: 'Standard') }}
            </span>
            @if($tourn?->tournament_type)
                <span class="badge bg-label-secondary px-3 py-1 rounded-pill fw-semibold">
                    {{ str_replace('_', ' ', $tourn->tournament_type) }}
                </span>
            @endif
        </div>
        <p class="text-muted small mb-0">
            Host Club: <strong class="text-dark">{{ $club?->club_name ?? $club?->name ?? '—' }}</strong>
            @if($tourn?->start_date)
                <span class="mx-2">•</span> Dates: <strong class="text-dark">{{ $tourn->start_date->format('M d, Y') }} — {{ $tourn->end_date?->format('M d, Y') ?? 'TBD' }}</strong>
            @endif
        </p>
    </div>
    <div class="admin-page-header__actions ms-auto">
        <a href="{{ route('admin.tournaments.index') }}" class="btn btn-outline-secondary btn-sm shadow-xs fw-semibold">
            <i class="mdi mdi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

@if($fixtureList->isNotEmpty())
    @foreach($fixtureList as $fItem)
        <div class="mb-4">
            <div class="row g-3 mb-3">
                <!-- Overview Card -->
                <div class="col-lg-6">
                    <div class="card h-100 fixture-card-custom">
                        <div class="fixture-card-header-custom d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0 fw-bold fs-6 fixture-page-title">
                                <i class="mdi mdi-trophy-outline me-2 text-dark"></i>Fixture #{{ $fItem->id }} Overview
                            </h5>
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
                            <span class="badge {{ $badgeClass }} px-2.5 py-1 fw-bold">{{ ucfirst($fItem->status) }}</span>
                        </div>
                        <div class="card-body pt-3 px-4">
                            <div class="row fixture-info-row">
                                <div class="col-4 text-muted fw-medium">Tournament:</div>
                                <div class="col-8">
                                    @if($tourn)
                                        <a href="{{ route('admin.tournaments.show', $tourn) }}" class="fixture-page-link">{{ $tourn->name }}</a>
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                            <div class="row fixture-info-row">
                                <div class="col-4 text-muted fw-medium">Group / Stage:</div>
                                <div class="col-8">
                                    @if($fItem->group)
                                        <span class="badge bg-label-primary px-2.5 py-1 fw-semibold">{{ $fItem->group->name }}</span>
                                    @else
                                        <span class="text-muted fw-medium">Knockout Stage</span>
                                    @endif
                                </div>
                            </div>
                            <div class="row fixture-info-row">
                                <div class="col-4 text-muted fw-medium">Round:</div>
                                <div class="col-8 fw-bold text-dark">{{ $fItem->round }}</div>
                            </div>
                            <div class="row fixture-info-row">
                                <div class="col-4 text-muted fw-medium">Fixture Status:</div>
                                <div class="col-8">
                                    <span class="badge {{ $badgeClass }} px-2.5 py-1 fw-bold">{{ ucfirst($fItem->status) }}</span>
                                </div>
                            </div>
                            <div class="row fixture-info-row">
                                <div class="col-4 text-muted fw-medium">Assigned Court:</div>
                                <div class="col-8">
                                    @if($fItem->court)
                                        <span class="badge bg-label-success px-2.5 py-1 fw-semibold">
                                            <i class="mdi mdi-map-marker-outline me-1"></i>{{ $fItem->court->name }} ({{ ucfirst($fItem->court->type) }})
                                        </span>
                                    @else
                                        <span class="text-muted">Unassigned at fixture level</span>
                                    @endif
                                </div>
                            </div>
                            <div class="row fixture-info-row">
                                <div class="col-4 text-muted fw-medium">Created At:</div>
                                <div class="col-8 text-dark fw-medium">{{ $fItem->created_at ? $fItem->created_at->format('Y-m-d H:i') : '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Matchup Card -->
                <div class="col-lg-6">
                    <div class="card h-100 fixture-card-custom">
                        <div class="fixture-card-header-custom">
                            <h5 class="card-title mb-0 fw-bold fs-6 fixture-page-title">
                                <i class="mdi mdi-swords me-2 text-dark"></i>Matchup Details
                            </h5>
                        </div>
                        <div class="card-body pt-3 px-4 d-flex flex-column justify-content-center">
                            @if($fItem->is_bye)
                                <div class="text-center py-4 bg-light rounded-3 border">
                                    <span class="badge bg-warning text-dark px-3 py-1.5 mb-2 fw-bold fs-7">BYE FIXTURE</span>
                                    <h5 class="fw-bold mb-1 text-dark">{{ $fItem->byeClub?->club_name ?? $fItem->byeClub?->name ?? '—' }}</h5>
                                    <small class="text-muted">This club receives an automatic bye in this round.</small>
                                </div>
                            @elseif($fItem->is_rest)
                                <div class="text-center py-4 bg-light rounded-3 border">
                                    <span class="badge bg-secondary p-2 mb-2 text-white fw-bold">REST FIXTURE</span>
                                    <h5 class="fw-bold mb-1 text-dark">{{ $fItem->restClub?->club_name ?? $fItem->restClub?->name ?? '—' }}</h5>
                                    <small class="text-muted">This club rests in this round.</small>
                                </div>
                            @else
                                @php
                                    $firstMatch = $fItem->matches?->first();
                                    $homePlayer = $firstMatch?->homePlayer;
                                    $awayPlayer = $firstMatch?->awayPlayer;

                                    $homeName = $fItem->homeClub?->club_name 
                                        ?? $fItem->homeClub?->name 
                                        ?? ($homePlayer?->name ?? ($fItem->home_placeholder ?: 'TBD'));

                                    $awayName = $fItem->awayClub?->club_name 
                                        ?? $fItem->awayClub?->name 
                                        ?? ($awayPlayer?->name ?? ($fItem->away_placeholder ?: 'TBD'));
                                @endphp
                                <div class="d-flex align-items-center justify-content-around text-center py-3">
                                    <!-- Home Side -->
                                    <div class="px-2 flex-fill">
                                        <div class="avatar avatar-md mx-auto mb-2 bg-label-primary rounded-circle d-flex align-items-center justify-content-center shadow-xs" style="width:52px;height:52px;">
                                            <i class="mdi mdi-domain fs-2"></i>
                                        </div>
                                        <h6 class="fw-bold mb-1 text-dark fs-6">{{ $homeName }}</h6>
                                        <span class="badge bg-label-primary px-2.5 py-0.5 fw-semibold">Home</span>
                                    </div>

                                    <!-- VS Center -->
                                    <div class="px-2">
                                        <span class="badge rounded-pill bg-dark text-white fs-6 fw-bold px-3 py-2 border shadow-xs">VS</span>
                                    </div>

                                    <!-- Away Side -->
                                    <div class="px-2 flex-fill">
                                        <div class="avatar avatar-md mx-auto mb-2 bg-label-danger rounded-circle d-flex align-items-center justify-content-center shadow-xs" style="width:52px;height:52px;">
                                            <i class="mdi mdi-domain fs-2"></i>
                                        </div>
                                        <h6 class="fw-bold mb-1 text-dark fs-6">{{ $awayName }}</h6>
                                        <span class="badge bg-label-danger px-2.5 py-0.5 fw-semibold">Away</span>
                                    </div>
                                </div>

                                @if($fItem->winnerClub || $fItem->winnerPlayer)
                                    <div class="alert alert-success text-center mt-3 mb-0 p-2.5 border-0 fw-bold">
                                        <i class="mdi mdi-trophy me-1 text-warning fs-5"></i> Winner: 
                                        <strong class="text-dark fs-6">{{ $fItem->winnerClub?->club_name ?? $fItem->winnerClub?->name ?? $fItem->winnerPlayer?->name }}</strong>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Matches List Table Card -->
            <div class="card fixture-card-custom">
                <div class="fixture-card-header-custom d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0 fw-bold fs-6 fixture-page-title">
                        <i class="mdi mdi-format-list-bulleted me-2 text-dark"></i>Matches for Fixture #{{ $fItem->id }} ({{ $fItem->matches->count() }})
                    </h6>
                </div>
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr class="bg-light">
                                <th class="text-dark fw-bold">Seq #</th>
                                <th class="text-dark fw-bold">Home Player</th>
                                <th class="text-dark fw-bold">Away Player</th>
                                <th class="text-dark fw-bold">Court / Venue</th>
                                <th class="text-dark fw-bold">Date & Time</th>
                                <th class="text-dark fw-bold">Status</th>
                                <th class="text-dark fw-bold">Score / Winner</th>
                                <th class="text-dark fw-bold">Officials</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($fItem->matches as $m)
                                <tr>
                                    <td><span class="fw-bold text-dark">#{{ $m->sequence }}</span></td>
                                    <td>
                                        @if($m->homePlayer)
                                            <span class="fw-bold text-dark">{{ $m->homePlayer->name }}</span>
                                        @else
                                            <span class="text-muted">{{ $m->home_player_placeholder ?: 'TBD' }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($m->awayPlayer)
                                            <span class="fw-bold text-dark">{{ $m->awayPlayer->name }}</span>
                                        @else
                                            <span class="text-muted">{{ $m->away_player_placeholder ?: 'TBD' }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($m->court)
                                            <span class="badge bg-label-success px-2.5 py-1 fw-semibold">
                                                <i class="mdi mdi-map-marker-outline me-1"></i>{{ $m->court->name }}
                                            </span>
                                        @elseif($m->venue_id)
                                            <span class="badge bg-label-info px-2.5 py-1">Venue ID: #{{ $m->venue_id }}</span>
                                        @else
                                            <span class="text-muted small">Unassigned</span>
                                        @endif
                                    </td>
                                    <td class="text-dark fw-medium">
                                        @if($m->start_date || $m->start_time)
                                            <span>{{ $m->start_date ? (is_string($m->start_date) ? $m->start_date : $m->start_date->format('Y-m-d')) : '' }} {{ $m->start_time }}</span>
                                        @else
                                            <span class="text-muted small">TBD</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-label-primary px-2.5 py-1 fw-bold">{{ ucfirst($m->status) }}</span>
                                    </td>
                                    <td>
                                        @if($m->score)
                                            <span class="fw-bold text-dark fs-6">{{ $m->score }}</span>
                                        @endif
                                        @if($m->winnerPlayer)
                                            <small class="d-block text-success fw-bold mt-0.5"><i class="mdi mdi-trophy-outline me-1"></i>{{ $m->winnerPlayer->name }}</small>
                                        @elseif(!$m->score)
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-dark small">
                                        @if($m->scorers->isNotEmpty())
                                            <small class="d-block text-dark fw-semibold">Scorer: {{ $m->scorers->pluck('name')->implode(', ') }}</small>
                                        @endif
                                        @if($m->umpires->isNotEmpty())
                                            <small class="d-block text-dark fw-semibold">Umpire: {{ $m->umpires->pluck('name')->implode(', ') }}</small>
                                        @endif
                                        @if($m->scorers->isEmpty() && $m->umpires->isEmpty())
                                            <span class="text-muted small">None</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted fw-medium">No individual matches scheduled for this fixture yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
@else
    <div class="card fixture-card-custom shadow-xs">
        <div class="card-body text-center py-5">
            <div class="mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center bg-light border"
                 style="width:72px; height:72px;">
                <i class="mdi mdi-tournament text-dark" style="font-size: 36px;"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">No Fixtures Generated</h5>
            <p class="text-muted mb-0 px-md-5 fs-6" style="max-width: 480px; margin: 0 auto;">
                No match fixtures or schedules have been generated for <strong>{{ $tourn?->name ?? 'this tournament' }}</strong> yet.
            </p>
        </div>
    </div>
@endif
@endsection

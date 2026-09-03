@extends('layouts/contentNavbarLayout')

@section('title', 'Match Detailed Score')

@section('content')
@php
    $tourn = $match->fixture?->tournament;
    $club = $tourn?->club;
    $fixture = $match->fixture;
    $homeUser = $match->homePlayer;
    $awayUser = $match->awayPlayer;
    $winnerUser = $match->winnerPlayer;

    $homeName = $homeUser?->name ?? $match->home_player_placeholder ?? 'Home Player';
    $awayName = $awayUser?->name ?? $match->away_player_placeholder ?? 'Away Player';
    $winnerName = $winnerUser?->name ?? ($match->winner_player_id ? ($match->winner_player_id == $match->home_player_id ? $homeName : $awayName) : null);

    $venueUser = $match->venue ?? ($match->venue_id ? \App\Models\User::find($match->venue_id) : null);
    $venueName = $venueUser?->club_name ?? $venueUser?->name;

    $statusMap = [
        'scheduled' => 'bg-label-primary',
        'live'      => 'bg-label-warning',
        'completed' => 'bg-label-success',
        'cancelled' => 'bg-label-danger',
    ];
    $badgeClass = $statusMap[$match->status] ?? 'bg-label-secondary';
@endphp

<style>
    .match-score-card {
        border: 1px solid #cbd5e1 !important;
        border-radius: 12px !important;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04) !important;
        background-color: #ffffff;
        margin-bottom: 24px;
        overflow: hidden;
    }

    .match-score-header {
        background-color: #f8fafc !important;
        border-bottom: 1px solid #e2e8f0 !important;
        padding: 16px 24px !important;
    }

    .vs-badge-hero {
        width: 48px;
        height: 48px;
        background-color: #000000 !important;
        color: #ffffff !important;
        font-weight: 900;
        font-size: 14px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .match-score-table th {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 16px !important;
    }

    .match-score-table td {
        padding: 14px 16px !important;
        vertical-align: middle;
    }
</style>

<!-- Page Header & Navigation -->
<div class="admin-page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div class="admin-page-header__left">
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold mb-0 text-dark">
                Match #{{ $match->sequence }} Detailed Score
            </h4>
            <span class="badge {{ $badgeClass }} px-3 py-1 rounded-pill ms-2 fw-semibold text-uppercase">
                {{ ucfirst($match->status) }}
            </span>
            @if($fixture?->round)
                <span class="badge bg-label-dark px-3 py-1 rounded-pill fw-semibold">
                    {{ $fixture->round }}
                </span>
            @endif
        </div>
        <p class="text-muted small mb-0">
            Tournament: <strong class="text-dark">{{ $tourn?->name ?? 'Squash Tournament' }}</strong>
            @if($club)
                <span class="mx-2">•</span> Host Club: <strong class="text-dark">{{ $club->club_name ?? $club->name }}</strong>
            @endif
        </p>
    </div>
    <div class="admin-page-header__actions ms-auto d-flex align-items-center gap-2">
        @if($tourn)
            <a href="{{ route('admin.tournaments.fixtures', $tourn->id) }}" class="btn btn-outline-secondary btn-sm shadow-xs fw-semibold">
                <i class="mdi mdi-arrow-left me-1"></i> Back
            </a>
        @elseif($fixture)
            <a href="{{ route('admin.fixtures.show', $fixture) }}" class="btn btn-outline-secondary btn-sm shadow-xs fw-semibold">
                <i class="mdi mdi-arrow-left me-1"></i> Back
            </a>
        @endif
        <a href="{{ route('admin.fixtures.index') }}" class="btn btn-outline-dark btn-sm shadow-xs fw-semibold">
            <i class="mdi mdi-view-list-outline me-1"></i> All Fixtures
        </a>
    </div>
</div>

<!-- Hero Match Score Banner Card -->
<div class="card match-score-card mb-4">
    <div class="card-body p-4">
        <div class="row align-items-center text-center">
            <!-- Home Player -->
            <div class="col-md-5 mb-3 mb-md-0">
                <div class="avatar avatar-xl mx-auto mb-2 bg-label-primary rounded-circle d-flex align-items-center justify-content-center shadow-xs" style="width:64px;height:64px;">
                    <i class="mdi mdi-account fs-2"></i>
                </div>
                <h5 class="fw-bold mb-1 text-dark fs-5">{{ $homeName }}</h5>
                <span class="badge bg-label-primary px-3 py-1 fw-semibold">Home Player</span>
            </div>

            <!-- Score / VS Center -->
            <div class="col-md-2 mb-3 mb-md-0">
                <div class="vs-badge-hero mb-2">VS</div>
                @if($match->score)
                    <div class="bg-dark text-white rounded px-3 py-1.5 fw-bold fs-4 d-inline-block shadow-sm">
                        {{ $match->score }}
                    </div>
                @else
                    <span class="badge bg-label-secondary px-3 py-1 fs-6">Scheduled</span>
                @endif

                @if($winnerName)
                    <div class="mt-2 text-success fw-bold small">
                        <i class="mdi mdi-trophy text-warning me-1 fs-5"></i> Winner: {{ $winnerName }}
                    </div>
                @endif
            </div>

            <!-- Away Player -->
            <div class="col-md-5">
                <div class="avatar avatar-xl mx-auto mb-2 bg-label-danger rounded-circle d-flex align-items-center justify-content-center shadow-xs" style="width:64px;height:64px;">
                    <i class="mdi mdi-account fs-2"></i>
                </div>
                <h5 class="fw-bold mb-1 text-dark fs-5">{{ $awayName }}</h5>
                <span class="badge bg-label-danger px-3 py-1 fw-semibold">Away Player</span>
            </div>
        </div>
    </div>
</div>

<!-- Details & Officials Cards Grid -->
<div class="row mb-4">
    <!-- Match Info & Timings -->
    <div class="col-md-6 mb-3 mb-md-0">
        <div class="card match-score-card h-100 mb-0">
            <div class="match-score-header">
                <h6 class="fw-bold text-dark mb-0"><i class="mdi mdi-information-outline me-2 text-primary"></i>Match Info & Rules</h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-6">
                        <span class="text-muted small d-block">Best of Format:</span>
                        <strong class="text-dark fs-6">Best of {{ $match->best_of ?? 3 }} Games</strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small d-block">Scoring Rules:</span>
                        <strong class="text-dark fs-6">PARS-11 (Win by 2)</strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small d-block">Start Time:</span>
                        <strong class="text-dark">{{ $match->match_start_time ? $match->match_start_time->format('Y-m-d H:i:s') : ($match->start_date ? $match->start_date . ' ' . $match->start_time : 'TBD') }}</strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small d-block">End Time:</span>
                        <strong class="text-dark">{{ $match->match_end_time ? $match->match_end_time->format('Y-m-d H:i:s') : '—' }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Location & Officials -->
    <div class="col-md-6">
        <div class="card match-score-card h-100 mb-0">
            <div class="match-score-header">
                <h6 class="fw-bold text-dark mb-0"><i class="mdi mdi-map-marker-radius me-2 text-info"></i>Venue, Court & Officials</h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-6">
                        <span class="text-muted small d-block">Assigned Venue:</span>
                        @if($venueName || $match->venue_id)
                            <span class="badge bg-label-info px-2.5 py-1 fw-semibold mt-1">
                                <i class="mdi mdi-domain me-1"></i>{{ $venueName ?: 'Venue' }}
                            </span>
                        @else
                            <strong class="text-muted small">Unassigned</strong>
                        @endif
                    </div>
                    <div class="col-6">
                        <span class="text-muted small d-block">Assigned Court:</span>
                        @if($match->court)
                            <span class="badge bg-label-success px-2.5 py-1 fw-semibold mt-1">
                                <i class="mdi mdi-map-marker-outline me-1"></i>{{ $match->court->name }}
                            </span>
                        @else
                            <strong class="text-muted small">Unassigned</strong>
                        @endif
                    </div>
                    <div class="col-6">
                        <span class="text-muted small d-block">Appointed Scorers:</span>
                        <strong class="text-dark">{{ $match->scorers->isNotEmpty() ? $match->scorers->pluck('name')->implode(', ') : 'None' }}</strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small d-block">Appointed Umpires:</span>
                        <strong class="text-dark">{{ $match->umpires->isNotEmpty() ? $match->umpires->pluck('name')->implode(', ') : 'None' }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Game-by-Game Score Breakdown Table -->
<div class="card match-score-card mb-4">
    <div class="match-score-header d-flex align-items-center justify-content-between">
        <h6 class="fw-bold text-dark mb-0"><i class="mdi mdi-trophy-variant-outline me-2 text-warning"></i>Games Score Breakdown</h6>
        <span class="badge bg-dark text-white rounded-pill px-3 py-1 fw-bold">{{ $match->games->count() }} {{ Str::plural('Game', $match->games->count()) }} Played</span>
    </div>
    @if($match->games && $match->games->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle text-center match-score-table">
                <thead class="bg-light">
                    <tr>
                        <th class="text-dark fw-bold">Game #</th>
                        <th class="text-dark fw-bold">{{ $homeName }} (Home)</th>
                        <th class="text-dark fw-bold">{{ $awayName }} (Away)</th>
                        <th class="text-dark fw-bold">Duration</th>
                        <th class="text-dark fw-bold">Status</th>
                        <th class="text-dark fw-bold">Game Winner</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($match->games as $g)
                        <tr>
                            <td class="fw-bold text-dark">Game {{ $g->game_number }}</td>
                            <td class="fw-bold text-primary fs-5">{{ $g->home_score }}</td>
                            <td class="fw-bold text-danger fs-5">{{ $g->away_score }}</td>
                            <td>
                                @if($g->duration_seconds)
                                    <span class="badge bg-label-secondary"><i class="mdi mdi-clock-outline me-1"></i>{{ floor($g->duration_seconds / 60) }}m {{ $g->duration_seconds % 60 }}s</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-label-primary text-uppercase fw-bold" style="font-size: 11px;">{{ ucfirst($g->status) }}</span>
                            </td>
                            <td>
                                @if($g->winner)
                                    <span class="badge bg-label-success fw-bold px-3 py-1"><i class="mdi mdi-trophy-outline me-1"></i>{{ $g->winner->name }}</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="card-body py-4 text-center text-muted">
            <i class="mdi mdi-information-outline d-block mb-1 fs-3 text-info"></i>
            No individual game breakdowns recorded yet for this match.
        </div>
    @endif
</div>

<!-- Rally-by-Rally History Log (Game-Wise) -->
@if($match->rallies && $match->rallies->where('is_undone', false)->isNotEmpty())
    @php
        $activeRallies = $match->rallies->where('is_undone', false)->sortBy('sequence');
        $ralliesByGame = $activeRallies->groupBy(function($r) {
            return $r->game?->game_number ?? 1;
        });
    @endphp

    <div class="card match-score-card mb-4">
        <div class="match-score-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h6 class="fw-bold text-dark mb-0"><i class="mdi mdi-history me-2 text-info"></i>Rally-by-Rally History Log</h6>
                <small class="text-muted">Total {{ $activeRallies->count() }} rallies played across {{ $ralliesByGame->count() }} {{ Str::plural('game', $ralliesByGame->count()) }}</small>
            </div>
            
            <ul class="nav nav-pills card-header-pills gap-1" id="rallyGameTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active btn-sm px-3 py-1 fw-semibold" id="tab-all-games" data-bs-toggle="tab" data-bs-target="#game-all-content" type="button" role="tab">
                        All Rallies ({{ $activeRallies->count() }})
                    </button>
                </li>
                @foreach($ralliesByGame->sortKeys() as $gNum => $gRallies)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link btn-sm px-3 py-1 fw-semibold" id="tab-game-{{ $gNum }}" data-bs-toggle="tab" data-bs-target="#game-{{ $gNum }}-content" type="button" role="tab">
                            Game {{ $gNum }} ({{ $gRallies->count() }})
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="card-body p-0">
            <div class="tab-content p-0" id="rallyGameTabContent">
                <!-- All Rallies Tab -->
                <div class="tab-pane fade show active" id="game-all-content" role="tabpanel">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover table-striped mb-0 text-center align-middle small match-score-table">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th class="text-dark fw-bold">Seq</th>
                                    <th class="text-dark fw-bold">Game</th>
                                    <th class="text-dark fw-bold">Server</th>
                                    <th class="text-dark fw-bold">Box</th>
                                    <th class="text-dark fw-bold">Call Type</th>
                                    <th class="text-dark fw-bold">Score After</th>
                                    <th class="text-dark fw-bold">Awarded To</th>
                                    <th class="text-dark fw-bold">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeRallies as $rallyItem)
                                    <tr>
                                        <td class="fw-bold">#{{ $rallyItem->sequence }}</td>
                                        <td><span class="badge bg-label-dark">G{{ $rallyItem->game?->game_number ?? 1 }}</span></td>
                                        <td class="fw-semibold text-dark">{{ $rallyItem->server?->name ?? ($rallyItem->server_player_id == $match->home_player_id ? $homeName : $awayName) }}</td>
                                        <td><span class="badge bg-label-info fw-bold">{{ $rallyItem->serving_side }}</span></td>
                                        <td><span class="badge bg-label-primary text-capitalize fw-bold">{{ str_replace('_', ' ', $rallyItem->call_type) }}</span></td>
                                        <td class="fw-bold fs-6 text-dark">{{ $rallyItem->home_score_after }}-{{ $rallyItem->away_score_after }}</td>
                                        <td class="fw-semibold text-success">{{ $rallyItem->awardedTo?->name ?? ($rallyItem->awarded_to_player_id ? ($rallyItem->awarded_to_player_id == $match->home_player_id ? $homeName : $awayName) : '—') }}</td>
                                        <td class="text-muted">{{ $rallyItem->event_time ? $rallyItem->event_time->format('H:i:s') : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Individual Game Tabs -->
                @foreach($ralliesByGame->sortKeys() as $gNum => $gRallies)
                    @php
                        $gameModel = $match->games ? $match->games->firstWhere('game_number', $gNum) : null;
                    @endphp
                    <div class="tab-pane fade" id="game-{{ $gNum }}-content" role="tabpanel">
                        <div class="bg-light px-4 py-2 border-bottom d-flex align-items-center justify-content-between">
                            <span class="fw-bold text-dark">
                                <i class="mdi mdi-tennis me-1 text-primary"></i> Game {{ $gNum }} Detailed Rallies Log
                            </span>
                            @if($gameModel)
                                <span class="badge bg-dark">
                                    Final Score: {{ $gameModel->home_score }} - {{ $gameModel->away_score }}
                                    ({{ ucfirst($gameModel->status) }})
                                </span>
                            @endif
                        </div>
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-hover table-striped mb-0 text-center align-middle small match-score-table">
                                <thead class="bg-light sticky-top">
                                    <tr>
                                        <th class="text-dark fw-bold">Rally #</th>
                                        <th class="text-dark fw-bold">Server</th>
                                        <th class="text-dark fw-bold">Box</th>
                                        <th class="text-dark fw-bold">Call Type</th>
                                        <th class="text-dark fw-bold">Score After</th>
                                        <th class="text-dark fw-bold">Awarded To</th>
                                        <th class="text-dark fw-bold">Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($gRallies as $index => $rallyItem)
                                        <tr>
                                            <td class="fw-bold">#{{ $index + 1 }} (Seq #{{ $rallyItem->sequence }})</td>
                                            <td class="fw-semibold text-dark">{{ $rallyItem->server?->name ?? ($rallyItem->server_player_id == $match->home_player_id ? $homeName : $awayName) }}</td>
                                            <td><span class="badge bg-label-info fw-bold">{{ $rallyItem->serving_side }}</span></td>
                                            <td><span class="badge bg-label-primary text-capitalize fw-bold">{{ str_replace('_', ' ', $rallyItem->call_type) }}</span></td>
                                            <td class="fw-bold fs-6 text-dark">{{ $rallyItem->home_score_after }}-{{ $rallyItem->away_score_after }}</td>
                                            <td class="fw-semibold text-success">{{ $rallyItem->awardedTo?->name ?? ($rallyItem->awarded_to_player_id ? ($rallyItem->awarded_to_player_id == $match->home_player_id ? $homeName : $awayName) : '—') }}</td>
                                            <td class="text-muted">{{ $rallyItem->event_time ? $rallyItem->event_time->format('H:i:s') : '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

@endsection

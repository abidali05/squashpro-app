@extends('layouts/contentNavbarLayout')

@section('title', 'Tournament Fixture Details')

@section('content')
@php
    $tourn = $fixture?->tournament ?? $tournament ?? null;
    $club = $tourn?->club ?? null;
    $fixtureList = $fixtures ?? ($fixture ? collect([$fixture]) : collect());

    // Extract unique rounds and groups for quick filter bar
    $rounds = $fixtureList->pluck('round')->filter()->unique()->values();
    $groups = $fixtureList->pluck('group.name')->filter()->unique()->values();
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

    /* Unified Fixture Card */
    .fixture-unified-card {
        border: 1px solid #cbd5e1 !important;
        border-radius: 12px !important;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04) !important;
        overflow: hidden;
        background-color: #ffffff;
        margin-bottom: 24px;
    }

    .fixture-unified-header {
        background-color: #f8fafc !important;
        border-bottom: 1px solid #e2e8f0 !important;
        padding: 14px 20px !important;
    }

    .fixture-matchup-box {
        background-color: #ffffff;
        border-bottom: 1px solid #f1f5f9;
        padding: 20px;
    }

    .vs-circle-pill {
        width: 44px;
        height: 44px;
        background-color: #000000 !important;
        color: #ffffff !important;
        font-weight: 900;
        font-size: 14px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        box-shadow: 0 3px 10px rgba(0,0,0,0.15);
    }

    .fixture-filter-btn {
        border: 1px solid #cbd5e1 !important;
        color: #000000 !important;
        background: #ffffff !important;
        font-weight: 700;
        border-radius: 20px;
        padding: 5px 16px;
        font-size: 12.5px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .fixture-filter-btn.active, .fixture-filter-btn:hover {
        background: #000000 !important;
        color: #ffffff !important;
        border-color: #000000 !important;
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
            <span class="mx-2">•</span> Total Fixtures: <strong class="text-dark">{{ $fixtureList->count() }}</strong>
        </p>
    </div>
    <div class="admin-page-header__actions ms-auto">
        <a href="{{ route('admin.tournaments.index') }}" class="btn btn-outline-secondary btn-sm shadow-xs fw-semibold">
            <i class="mdi mdi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<!-- Round / Group Filter Pills -->
@if($rounds->count() > 1 || $groups->count() > 0)
    <div class="d-flex align-items-center gap-2 mb-4 flex-wrap bg-white p-3 rounded border shadow-xs">
        <span class="fw-bold text-dark me-2 fs-7"><i class="mdi mdi-filter-variant me-1"></i> Filter Fixtures:</span>
        <button type="button" class="fixture-filter-btn active" data-filter="all">All Fixtures</button>
        
        @foreach($rounds as $rnd)
            <button type="button" class="fixture-filter-btn" data-filter="round-{{ Str::slug($rnd) }}">{{ $rnd }}</button>
        @endforeach

        @foreach($groups as $grp)
            <button type="button" class="fixture-filter-btn" data-filter="group-{{ Str::slug($grp) }}">{{ $grp }}</button>
        @endforeach
    </div>
@endif

@if($fixtureList->isNotEmpty())
    @foreach($fixtureList as $fItem)
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

            $statusMap = [
                'scheduled' => 'bg-label-primary',
                'bye'       => 'bg-label-warning',
                'rest'      => 'bg-label-secondary',
                'completed' => 'bg-label-success',
                'cancelled' => 'bg-label-danger',
            ];
            $badgeClass = $statusMap[$fItem->status] ?? 'bg-label-secondary';

            $roundSlug = 'round-' . Str::slug($fItem->round);
            $groupSlug = $fItem->group?->name ? 'group-' . Str::slug($fItem->group->name) : 'group-knockout';

            $fVenueId = $fItem->matches->pluck('venue_id')->filter()->first();
            $fVenueUser = $fVenueId ? ($firstMatch?->venue ?? \App\Models\User::find($fVenueId)) : null;
            $fVenueName = $fVenueUser?->club_name ?? $fVenueUser?->name;
        @endphp

        <!-- Unified Single Card for Fixture -->
        <div class="card fixture-unified-card fixture-filter-item" data-round="{{ $roundSlug }}" data-group="{{ $groupSlug }}">
            <!-- Fixture Header Bar -->
            <div class="fixture-unified-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-dark text-white fw-bold px-2.5 py-1">Fixture #{{ $fItem->id }}</span>
                    <span class="fw-bold text-dark fs-6 ms-1">{{ $fItem->round }}</span>
                    <span class="text-muted">•</span>
                    @if($fItem->group)
                        <span class="badge bg-label-primary px-2.5 py-1 fw-semibold">{{ $fItem->group->name }}</span>
                    @else
                        <span class="badge bg-label-secondary px-2.5 py-1 fw-semibold">Knockout Stage</span>
                    @endif

                    @if($fItem->court)
                        <span class="badge bg-label-success px-2.5 py-1 fw-semibold ms-1">
                            <i class="mdi mdi-map-marker-outline me-1"></i>{{ $fItem->court->name }}
                        </span>
                    @endif

                    @if($fVenueId)
                        <span class="badge bg-label-info px-2.5 py-1 fw-semibold ms-1">
                            <i class="mdi mdi-domain me-1"></i>{{ $fVenueName ? $fVenueName . ' (Venue #' . $fVenueId . ')' : 'Venue #' . $fVenueId }}
                        </span>
                    @endif
                </div>

                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted small"><i class="mdi mdi-clock-outline me-1"></i>{{ $fItem->created_at ? $fItem->created_at->format('Y-m-d H:i') : '—' }}</span>
                    <span class="badge {{ $badgeClass }} px-3 py-1 fw-bold fs-7">{{ ucfirst($fItem->status) }}</span>
                </div>
            </div>

            <!-- Fixture Competitors / Matchup Banner -->
            <div class="fixture-matchup-box">
                @if($fItem->is_bye)
                    <div class="text-center py-3 bg-light rounded border">
                        <span class="badge bg-warning text-dark px-3 py-1 mb-1 fw-bold">BYE FIXTURE</span>
                        <h5 class="fw-bold text-dark mb-0 fs-6">{{ $fItem->byeClub?->club_name ?? $fItem->byeClub?->name ?? '—' }}</h5>
                        <small class="text-muted">This club receives an automatic bye in this round.</small>
                    </div>
                @elseif($fItem->is_rest)
                    <div class="text-center py-3 bg-light rounded border">
                        <span class="badge bg-secondary text-white px-3 py-1 mb-1 fw-bold">REST FIXTURE</span>
                        <h5 class="fw-bold text-dark mb-0 fs-6">{{ $fItem->restClub?->club_name ?? $fItem->restClub?->name ?? '—' }}</h5>
                        <small class="text-muted">This club rests in this round.</small>
                    </div>
                @else
                    <div class="row align-items-center text-center py-2">
                        <!-- Home -->
                        <div class="col-5">
                            <div class="avatar avatar-md mx-auto mb-1 bg-label-primary rounded-circle d-flex align-items-center justify-content-center shadow-xs" style="width:48px;height:48px;">
                                <i class="mdi mdi-domain fs-3"></i>
                            </div>
                            <h6 class="fw-bold mb-0 text-dark fs-6">{{ $homeName }}</h6>
                            <span class="badge bg-label-primary mt-1 px-2 py-0.5 fw-semibold">Home</span>
                        </div>

                        <!-- VS -->
                        <div class="col-2">
                            <div class="vs-circle-pill">VS</div>
                        </div>

                        <!-- Away -->
                        <div class="col-5">
                            <div class="avatar avatar-md mx-auto mb-1 bg-label-danger rounded-circle d-flex align-items-center justify-content-center shadow-xs" style="width:48px;height:48px;">
                                <i class="mdi mdi-domain fs-3"></i>
                            </div>
                            <h6 class="fw-bold mb-0 text-dark fs-6">{{ $awayName }}</h6>
                            <span class="badge bg-label-danger mt-1 px-2 py-0.5 fw-semibold">Away</span>
                        </div>
                    </div>

                    @if($fItem->winnerClub || $fItem->winnerPlayer)
                        <div class="alert alert-success text-center mt-3 mb-0 p-2 border-0 fw-bold">
                            <i class="mdi mdi-trophy me-1 text-warning fs-5"></i> Winner: 
                            <strong class="text-dark fs-6">{{ $fItem->winnerClub?->club_name ?? $fItem->winnerClub?->name ?? $fItem->winnerPlayer?->name }}</strong>
                        </div>
                    @endif
                @endif
            </div>

            <!-- Matches Schedule Table -->
            @if($fItem->matches->isNotEmpty())
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr class="bg-light">
                                <th class="text-dark fw-bold ps-4">Seq #</th>
                                <th class="text-dark fw-bold">Home Player</th>
                                <th class="text-dark fw-bold">Away Player</th>
                                <th class="text-dark fw-bold">Court / Venue</th>
                                <th class="text-dark fw-bold">Date & Time</th>
                                <th class="text-dark fw-bold">Status</th>
                                <th class="text-dark fw-bold">Score / Winner</th>
                                <th class="text-dark fw-bold pe-4">Officials</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fItem->matches as $m)
                                @php
                                    $mVenueUser = $m->venue ?? ($m->venue_id ? \App\Models\User::find($m->venue_id) : null);
                                    $mVenueName = $mVenueUser?->club_name ?? $mVenueUser?->name;
                                @endphp
                                <tr>
                                    <td class="ps-4"><span class="fw-bold text-dark">#{{ $m->sequence }}</span></td>
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
                                            <span class="badge bg-label-success px-2.5 py-1 fw-semibold me-1">
                                                <i class="mdi mdi-map-marker-outline me-1"></i>{{ $m->court->name }}
                                            </span>
                                        @endif

                                        @if($m->venue_id || $mVenueName)
                                            <span class="badge bg-label-info px-2.5 py-1 fw-semibold">
                                                <i class="mdi mdi-domain me-1"></i>{{ $mVenueName ? $mVenueName . ' (Venue #' . $m->venue_id . ')' : 'Venue #' . $m->venue_id }}
                                            </span>
                                        @endif

                                        @if(!$m->court && !$m->venue_id && !$mVenueName)
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
                                    <td class="pe-4 text-dark small">
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
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-4 py-3 bg-light text-muted small border-top">
                    <i class="mdi mdi-information-outline me-1 text-info"></i> No individual matches scheduled for this fixture.
                </div>
            @endif
        </div>
    @endforeach
@else
    <div class="card fixture-unified-card shadow-xs">
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filterBtns = document.querySelectorAll('[data-filter]');
        const fixtureCards = document.querySelectorAll('.fixture-filter-item');

        filterBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const filterVal = this.getAttribute('data-filter');

                fixtureCards.forEach(card => {
                    if (filterVal === 'all') {
                        card.style.display = 'block';
                    } else {
                        const cardRound = card.getAttribute('data-round');
                        const cardGroup = card.getAttribute('data-group');
                        if (cardRound === filterVal || cardGroup === filterVal) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    }
                });
            });
        });
    });
</script>
@endsection

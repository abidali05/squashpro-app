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

    // Build Club Leaderboard / Standings
    $clubStats = [];

    $addClubToStats = function ($clubUser, $role = 'Participating') use (&$clubStats) {
        if (!$clubUser || !isset($clubUser->id)) return;
        if (!isset($clubStats[$clubUser->id])) {
            $cName = $clubUser->club_name ?? $clubUser->name ?? ('Club #' . $clubUser->id);
            $cLogo = app_image_url($clubUser->club_logo);
            $clubStats[$clubUser->id] = [
                'id' => (int) $clubUser->id,
                'name' => $cName,
                'logo' => $cLogo,
                'role' => $role,
                'fixtures_played' => 0,
                'fixtures_won' => 0,
                'matches_played' => 0,
                'matches_won' => 0,
                'matches_lost' => 0,
            ];
        }
    };

    if ($club) {
        $addClubToStats($club, 'Host');
    }

    $opponentClubIds = is_array($tourn?->opponent_club_id) 
        ? $tourn->opponent_club_id 
        : json_decode($tourn?->opponent_club_id ?? '[]', true);

    if (!empty($opponentClubIds)) {
        $opponents = \App\Models\User::whereIn('id', (array) $opponentClubIds)->get();
        foreach ($opponents as $opp) {
            $addClubToStats($opp, 'Invited');
        }
    }

    foreach ($fixtureList as $fItem) {
        if ($fItem->homeClub) $addClubToStats($fItem->homeClub);
        if ($fItem->awayClub) $addClubToStats($fItem->awayClub);
        if ($fItem->byeClub) $addClubToStats($fItem->byeClub);
        if ($fItem->restClub) $addClubToStats($fItem->restClub);
        if ($fItem->winnerClub) $addClubToStats($fItem->winnerClub);

        $hId = $fItem->home_club_id;
        $aId = $fItem->away_club_id;
        $wClubId = $fItem->winner_club_id;

        $hMatchWins = 0;
        $aMatchWins = 0;

        foreach ($fItem->matches as $m) {
            $wPId = $m->winner_player_id;
            $hPId = $m->home_player_id;
            $aPId = $m->away_player_id;

            if ($m->status === 'completed' || $wPId) {
                if ($hId && isset($clubStats[$hId])) $clubStats[$hId]['matches_played']++;
                if ($aId && isset($clubStats[$aId])) $clubStats[$aId]['matches_played']++;

                if ($wPId && $wPId == $hPId) {
                    $hMatchWins++;
                    if ($hId && isset($clubStats[$hId])) $clubStats[$hId]['matches_won']++;
                    if ($aId && isset($clubStats[$aId])) $clubStats[$aId]['matches_lost']++;
                } elseif ($wPId && $wPId == $aPId) {
                    $aMatchWins++;
                    if ($aId && isset($clubStats[$aId])) $clubStats[$aId]['matches_won']++;
                    if ($hId && isset($clubStats[$hId])) $clubStats[$hId]['matches_lost']++;
                }
            }
        }

        if ($fItem->status === 'completed' || ($hMatchWins + $aMatchWins > 0)) {
            if ($hId && isset($clubStats[$hId])) $clubStats[$hId]['fixtures_played']++;
            if ($aId && isset($clubStats[$aId])) $clubStats[$aId]['fixtures_played']++;
        }

        if ($wClubId && isset($clubStats[$wClubId])) {
            $clubStats[$wClubId]['fixtures_won']++;
        } elseif ($hMatchWins > $aMatchWins && $hId && isset($clubStats[$hId])) {
            $clubStats[$hId]['fixtures_won']++;
        } elseif ($aMatchWins > $hMatchWins && $aId && isset($clubStats[$aId])) {
            $clubStats[$aId]['fixtures_won']++;
        }
    }

    $leaderboard = collect($clubStats)->sortByDesc(function ($item) {
        return ($item['matches_won'] * 10000) + ($item['fixtures_won'] * 1000) - $item['matches_lost'];
    })->values();
@endphp

<style>
    .fixture-page-title {
        color: #000000 !important;
        font-weight: 700;
        font-size: 1.15rem !important;
    }
    .fixture-page-link {
        color: #000000 !important;
        font-weight: 700;
        text-decoration: underline;
        font-size: 12.5px !important;
    }
    .fixture-page-link:hover {
        color: #333333 !important;
    }

    /* Unified Fixture Card */
    .fixture-unified-card {
        border: 1px solid #cbd5e1 !important;
        border-radius: 10px !important;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.03) !important;
        overflow: hidden;
        background-color: #ffffff;
        margin-bottom: 20px;
    }

    .fixture-unified-header {
        background-color: #f8fafc !important;
        border-bottom: 1px solid #e2e8f0 !important;
        padding: 10px 16px !important;
    }

    .fixture-matchup-box {
        background-color: #ffffff;
        border-bottom: 1px solid #f1f5f9;
        padding: 14px 16px;
    }

    .vs-circle-pill {
        width: 36px;
        height: 36px;
        background-color: #000000 !important;
        color: #ffffff !important;
        font-weight: 800;
        font-size: 12px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        box-shadow: 0 2px 8px rgba(0,0,0,0.12);
    }

    .fixture-filter-btn {
        border: 1px solid #cbd5e1 !important;
        color: #000000 !important;
        background: #ffffff !important;
        font-weight: 600;
        border-radius: 18px;
        padding: 5px 14px;
        font-size: 11.5px !important;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .fixture-filter-btn.active, .fixture-filter-btn:hover {
        background: #000000 !important;
        color: #ffffff !important;
        border-color: #000000 !important;
    }

    /* Match Table Styling - Compact Font Sizes */
    .fixture-matches-table th {
        font-size: 11px !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 8px 12px !important;
    }

    .fixture-matches-table td {
        padding: 10px 12px !important;
        vertical-align: middle;
        font-size: 12.5px !important;
    }
</style>

<!-- Page Header -->
<div class="admin-page-header mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="admin-page-header__left">
        <div class="d-flex align-items-center gap-2 mb-1">
            <h5 class="fw-bold mb-0 text-dark fs-5">
                {{ $tourn?->name ?? 'Match Fixtures' }}
            </h5>
            <span class="badge bg-label-primary px-2.5 py-0.5 rounded-pill ms-1 fw-semibold small" style="font-size: 11px;">
                {{ ucfirst(($tourn?->format) ?: 'Standard') }}
            </span>
            @if($tourn?->tournament_type)
                <span class="badge bg-label-secondary px-2.5 py-0.5 rounded-pill fw-semibold small" style="font-size: 11px;">
                    {{ str_replace('_', ' ', $tourn->tournament_type) }}
                </span>
            @endif
        </div>
        <p class="text-muted small mb-0" style="font-size: 12px;">
            Host Club: <strong class="text-dark">{{ $club?->club_name ?? $club?->name ?? '—' }}</strong>
            @if($tourn?->start_date)
                <span class="mx-1.5">•</span> Dates: <strong class="text-dark">{{ $tourn->start_date->format('M d, Y') }} — {{ $tourn->end_date?->format('M d, Y') ?? 'TBD' }}</strong>
            @endif
            <span class="mx-1.5">•</span> Total Fixtures: <strong class="text-dark">{{ $fixtureList->count() }}</strong>
        </p>
    </div>
    <div class="admin-page-header__actions ms-auto">
        <a href="{{ route('admin.tournaments.index') }}" class="btn btn-outline-secondary btn-sm shadow-xs fw-semibold py-1 px-3" style="font-size: 12px;">
            <i class="mdi mdi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<!-- Club Standings / Leaderboard Table -->
@if($leaderboard->isNotEmpty())
    <div class="card mb-3 border-0 shadow-xs rounded-3 overflow-hidden">
        <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <div class="avatar avatar-xs bg-label-primary rounded-circle d-flex align-items-center justify-content-center" style="width:28px;height:28px;">
                    <i class="mdi mdi-trophy-outline fs-6 text-primary"></i>
                </div>
                <div>
                    <h6 class="card-title mb-0 text-dark fw-bold" style="font-size: 13.5px;">Club Leaderboard</h6>
                    <small class="text-muted" style="font-size: 11.5px;">Matches won and overall standings across all tournament fixtures</small>
                </div>
            </div>
            <span class="badge bg-label-secondary px-2.5 py-1 rounded-pill fw-semibold" style="font-size: 11px;">
                {{ $leaderboard->count() }} {{ Str::plural('Club', $leaderboard->count()) }} Participating
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-borderless table-hover align-middle mb-0" style="font-size: 12px;">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3 text-uppercase text-muted fw-bold" style="width: 70px; font-size: 11px;">Rank</th>
                        <th class="text-uppercase text-muted fw-bold" style="font-size: 11px;">Club Name</th>
                        <th class="text-center text-uppercase text-muted fw-bold" style="width: 120px; font-size: 11px;">Fixtures Played</th>
                        <th class="text-center text-uppercase text-muted fw-bold" style="width: 120px; font-size: 11px;">Matches Won</th>
                        <th class="text-center text-uppercase text-muted fw-bold" style="width: 120px; font-size: 11px;">Matches Lost</th>
                        <th class="text-center text-uppercase text-muted fw-bold" style="width: 120px; font-size: 11px;">Fixtures Won</th>
                        <th class="pe-3 text-end text-uppercase text-muted fw-bold" style="width: 110px; font-size: 11px;">Win Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($leaderboard as $index => $item)
                        @php
                            $rank = $index + 1;
                            $totalMatches = $item['matches_won'] + $item['matches_lost'];
                            $winRate = $totalMatches > 0 ? round(($item['matches_won'] / $totalMatches) * 100) : 0;
                        @endphp
                        <tr>
                            <td class="ps-3 py-2">
                                @if($rank === 1)
                                    <span class="badge bg-warning text-dark fw-bold px-2 py-0.5 rounded-pill shadow-xs" style="font-size: 10.5px;">🥇 #1</span>
                                @elseif($rank === 2)
                                    <span class="badge bg-secondary text-white fw-bold px-2 py-0.5 rounded-pill shadow-xs" style="font-size: 10.5px;">🥈 #2</span>
                                @elseif($rank === 3)
                                    <span class="badge bg-danger text-white fw-bold px-2 py-0.5 rounded-pill shadow-xs" style="font-size: 10.5px;">🥉 #3</span>
                                @else
                                    <span class="fw-bold text-muted ms-1" style="font-size: 11.5px;">#{{ $rank }}</span>
                                @endif
                            </td>
                            <td class="py-2">
                                <div class="d-flex align-items-center gap-2.5">
                                    @if($item['logo'])
                                        <img src="{{ $item['logo'] }}" alt="{{ $item['name'] }}" class="rounded-circle border" style="width: 30px; height: 30px; object-fit: cover;">
                                    @else
                                        <div class="avatar avatar-xs bg-label-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 30px; height: 30px; font-size: 12px;">
                                            <i class="mdi mdi-domain"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <h6 class="mb-0 text-dark fw-bold" style="font-size: 12.5px;">{{ $item['name'] }}</h6>
                                        @if(isset($item['role']) && $item['role'] === 'Host')
                                            <span class="badge bg-label-info px-1.5 py-0.2" style="font-size: 10px;">Host Club</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-center py-2">
                                <span class="fw-bold text-dark" style="font-size: 12.5px;">{{ $item['fixtures_played'] }}</span>
                            </td>
                            <td class="text-center py-2">
                                <span class="badge bg-success text-white px-2.5 py-1 fw-bold rounded-pill shadow-xs" style="font-size: 11.5px;">
                                    <i class="mdi mdi-trophy-variant-outline me-1"></i>{{ $item['matches_won'] }}
                                </span>
                            </td>
                            <td class="text-center py-2">
                                <span class="badge bg-label-secondary text-muted px-2.5 py-1 fw-bold rounded-pill" style="font-size: 11.5px;">
                                    {{ $item['matches_lost'] }}
                                </span>
                            </td>
                            <td class="text-center py-2">
                                <span class="fw-bold text-dark" style="font-size: 12.5px;">{{ $item['fixtures_won'] }}</span>
                            </td>
                            <td class="pe-3 text-end py-2">
                                <div class="d-flex align-items-center justify-content-end gap-1.5">
                                    <span class="fw-bold text-dark" style="font-size: 12px;">{{ $winRate }}%</span>
                                    <div class="progress flex-grow-1" style="height: 5px; max-width: 50px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $winRate }}%" aria-valuenow="{{ $winRate }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<!-- Round / Group Filter Pills -->
@if($rounds->count() > 1 || $groups->count() > 0)
    <div class="d-flex align-items-center gap-2 mb-4 flex-wrap bg-white rounded-3 border shadow-xs" style="padding: 12px 20px !important;">
        <span class="fw-bold text-dark me-2" style="font-size: 12px;"><i class="mdi mdi-filter-variant me-1"></i> Filter Fixtures:</span>
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
            <div class="fixture-unified-header d-flex align-items-center justify-content-between flex-wrap gap-2 py-2 px-3">
                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                    <span class="badge bg-dark text-white fw-bold px-2 py-0.5" style="font-size: 10.5px;">Fixture #{{ $fItem->id }}</span>
                    <span class="fw-bold text-dark ms-1" style="font-size: 12.5px;">{{ $fItem->round }}</span>
                    <span class="text-muted small">•</span>
                    @if($fItem->group)
                        <span class="badge bg-label-primary px-2 py-0.5 fw-semibold" style="font-size: 10.5px;">{{ $fItem->group->name }}</span>
                    @else
                        <span class="badge bg-label-secondary px-2 py-0.5 fw-semibold" style="font-size: 10.5px;">Knockout Stage</span>
                    @endif

                    @if($fVenueId || $fVenueName)
                        <span class="badge bg-label-info px-2 py-0.5 fw-semibold ms-1" style="font-size: 10.5px;">
                            <i class="mdi mdi-domain me-1"></i>{{ $fVenueName ?: 'Venue' }}
                        </span>
                    @endif

                    @if($fItem->court)
                        <span class="badge bg-label-success px-2 py-0.5 fw-semibold ms-1" style="font-size: 10.5px;">
                            <i class="mdi mdi-map-marker-outline me-1"></i>{{ $fItem->court->name }}
                        </span>
                    @endif
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="font-size: 11px;"><i class="mdi mdi-clock-outline me-1"></i>{{ $fItem->created_at ? $fItem->created_at->format('Y-m-d H:i') : '—' }}</span>
                    <span class="badge {{ $badgeClass }} px-2.5 py-0.5 fw-bold" style="font-size: 11px;">{{ ucfirst($fItem->status) }}</span>
                </div>
            </div>

            <!-- Fixture Competitors / Matchup Banner -->
            <div class="fixture-matchup-box py-2.5 px-3">
                @if($fItem->is_bye)
                    <div class="text-center py-2 bg-light rounded border">
                        <span class="badge bg-warning text-dark px-2.5 py-0.5 mb-1 fw-bold" style="font-size: 10.5px;">BYE FIXTURE</span>
                        <h6 class="fw-bold text-dark mb-0" style="font-size: 13px;">{{ $fItem->byeClub?->club_name ?? $fItem->byeClub?->name ?? '—' }}</h6>
                        <small class="text-muted" style="font-size: 11px;">This club receives an automatic bye in this round.</small>
                    </div>
                @elseif($fItem->is_rest)
                    <div class="text-center py-2 bg-light rounded border">
                        <span class="badge bg-secondary text-white px-2.5 py-0.5 mb-1 fw-bold" style="font-size: 10.5px;">REST FIXTURE</span>
                        <h6 class="fw-bold text-dark mb-0" style="font-size: 13px;">{{ $fItem->restClub?->club_name ?? $fItem->restClub?->name ?? '—' }}</h6>
                        <small class="text-muted" style="font-size: 11px;">This club rests in this round.</small>
                    </div>
                @else
                    <div class="row align-items-center text-center py-1">
                        <!-- Home -->
                        <div class="col-5">
                            <div class="avatar avatar-sm mx-auto mb-1 bg-label-primary rounded-circle d-flex align-items-center justify-content-center shadow-xs" style="width:36px;height:36px;">
                                <i class="mdi mdi-domain fs-5"></i>
                            </div>
                            <h6 class="fw-bold mb-0 text-dark" style="font-size: 13px;">{{ $homeName }}</h6>
                            <span class="badge bg-label-primary mt-0.5 px-2 py-0.2 fw-semibold" style="font-size: 10px;">Home</span>
                        </div>

                        <!-- VS -->
                        <div class="col-2">
                            <div class="vs-circle-pill">VS</div>
                        </div>

                        <!-- Away -->
                        <div class="col-5">
                            <div class="avatar avatar-sm mx-auto mb-1 bg-label-danger rounded-circle d-flex align-items-center justify-content-center shadow-xs" style="width:36px;height:36px;">
                                <i class="mdi mdi-domain fs-5"></i>
                            </div>
                            <h6 class="fw-bold mb-0 text-dark" style="font-size: 13px;">{{ $awayName }}</h6>
                            <span class="badge bg-label-danger mt-0.5 px-2 py-0.2 fw-semibold" style="font-size: 10px;">Away</span>
                        </div>
                    </div>

                    @if($fItem->winnerClub || $fItem->winnerPlayer)
                        <div class="alert alert-success text-center mt-2 mb-0 p-1.5 border-0 fw-bold" style="font-size: 12px;">
                            <i class="mdi mdi-trophy me-1 text-warning fs-6"></i> Winner: 
                            <strong class="text-dark" style="font-size: 12.5px;">{{ $fItem->winnerClub?->club_name ?? $fItem->winnerClub?->name ?? $fItem->winnerPlayer?->name }}</strong>
                        </div>
                    @endif
                @endif
            </div>

            <!-- Matches Schedule Table -->
            @if($fItem->matches->isNotEmpty())
                <div class="table-responsive border-top">
                    <table class="table table-hover table-striped mb-0 align-middle fixture-matches-table" style="font-size: 12px;">
                        <thead>
                            <tr class="bg-light">
                                <th class="text-dark fw-bold ps-3" style="width: 60px; font-size: 11px;">Seq #</th>
                                <th class="text-dark fw-bold" style="min-width: 120px; font-size: 11px;">Home Player</th>
                                <th class="text-dark fw-bold" style="min-width: 120px; font-size: 11px;">Away Player</th>
                                <th class="text-dark fw-bold" style="min-width: 140px; font-size: 11px;">Venue / Court</th>
                                <th class="text-dark fw-bold" style="min-width: 110px; font-size: 11px;">Date & Time</th>
                                <th class="text-dark fw-bold" style="width: 90px; font-size: 11px;">Status</th>
                                <th class="text-dark fw-bold" style="min-width: 90px; font-size: 11px;">Score</th>
                                <th class="text-dark fw-bold pe-3 text-end" style="width: 120px; font-size: 11px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fItem->matches as $m)
                                @php
                                    $mVenueUser = $m->venue ?? ($m->venue_id ? \App\Models\User::find($m->venue_id) : null);
                                    $mVenueName = $mVenueUser?->club_name ?? $mVenueUser?->name;
                                @endphp
                                <tr>
                                    <td class="ps-3 py-2"><span class="fw-bold text-dark" style="font-size: 11.5px;">#{{ $m->sequence }}</span></td>
                                    <td class="py-2">
                                        @if($m->homePlayer)
                                            <span class="fw-bold text-dark" style="font-size: 12px;">{{ $m->homePlayer->name }}</span>
                                        @else
                                            <span class="text-muted" style="font-size: 11.5px;">{{ $m->home_player_placeholder ?: 'TBD' }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2">
                                        @if($m->awayPlayer)
                                            <span class="fw-bold text-dark" style="font-size: 12px;">{{ $m->awayPlayer->name }}</span>
                                        @else
                                            <span class="text-muted" style="font-size: 11.5px;">{{ $m->away_player_placeholder ?: 'TBD' }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2">
                                        <div class="d-flex flex-column align-items-start gap-0.5">
                                            @if($mVenueName || $m->venue_id)
                                                <span class="badge bg-label-info px-1.5 py-0.5 fw-semibold text-wrap text-start" style="font-size: 10px;">
                                                    <i class="mdi mdi-domain me-1"></i>{{ $mVenueName ?: 'Venue' }}
                                                </span>
                                            @endif

                                            @if($m->court)
                                                <span class="badge bg-label-success px-1.5 py-0.5 fw-semibold text-wrap text-start" style="font-size: 10px;">
                                                    <i class="mdi mdi-map-marker-outline me-1"></i>{{ $m->court->name }}
                                                </span>
                                            @endif

                                            @if(!$m->court && !$m->venue_id && !$mVenueName)
                                                <span class="text-muted small" style="font-size: 10.5px;">Unassigned</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-dark fw-medium py-2" style="font-size: 11.5px;">
                                        @if($m->start_date || $m->start_time)
                                            <span class="fw-bold text-dark d-block text-nowrap"><i class="mdi mdi-calendar-outline me-1 text-primary"></i>{{ $m->start_date ? (is_string($m->start_date) ? $m->start_date : $m->start_date->format('Y-m-d')) : '' }}</span>
                                            @if($m->start_time)
                                                <small class="text-muted d-block mt-0.5 text-nowrap"><i class="mdi mdi-clock-outline me-1 text-info"></i>{{ $m->start_time }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted small" style="font-size: 11px;">TBD</span>
                                        @endif
                                    </td>
                                    <td class="py-2">
                                        <span class="badge bg-label-primary px-2 py-0.5 fw-bold" style="font-size: 10.5px;">{{ ucfirst($m->status) }}</span>
                                    </td>
                                    <td class="py-2">
                                        @if($m->score)
                                            <span class="fw-bold text-dark d-block" style="font-size: 12px;">{{ $m->score }}</span>
                                        @else
                                            <span class="text-muted small" style="font-size: 11px;">—</span>
                                        @endif
                                    </td>
                                    <td class="pe-3 text-end py-2">
                                        <a href="{{ route('admin.matches.show', $m) }}" class="btn btn-xs btn-outline-primary shadow-xs fw-semibold text-nowrap py-1 px-2" style="font-size: 11px;">
                                            <i class="mdi mdi-chart-box-outline me-1"></i>Detail Score
                                        </a>
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

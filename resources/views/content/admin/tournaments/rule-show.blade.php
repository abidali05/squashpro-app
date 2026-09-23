@extends('layouts/contentNavbarLayout')

@section('title', 'Tournament Rules Details')

@section('content')
@php
    $tourn = $tournamentRule?->tournament ?? $tournament ?? null;
    $club = $tournamentRule?->club ?? $tourn?->club ?? null;
@endphp

<style>
    .rule-page-black-title {
        color: #000000 !important;
    }
    .rule-page-black-link {
        color: #000000 !important;
        font-weight: 700;
        text-decoration: underline;
    }
    .rule-page-black-link:hover {
        color: #333333 !important;
    }
</style>

<div class="admin-page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div class="admin-page-header__left">
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold mb-0 text-dark">
                {{ $tourn?->name ?? 'Tournament Rules' }}
            </h4>
            <span class="badge bg-label-primary px-2.5 py-1 rounded-pill ms-2 fw-semibold">
                {{ ucfirst(($tournamentRule?->tournament_format ?: $tourn?->format) ?: 'Standard') }}
            </span>
        </div>
        <p class="text-muted small mb-0">
            Host Club: <strong class="text-dark">{{ $club?->club_name ?? $club?->name ?? '—' }}</strong>
        </p>
    </div>
    <div class="admin-page-header__actions ms-auto">
        <a href="{{ route('admin.tournaments.index') }}" class="btn btn-outline-secondary btn-sm shadow-xs fw-semibold">
            <i class="mdi mdi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <!-- Overview & Basic Details -->
    <div class="col-md-4 mb-4">
        <div class="card h-100 border shadow-xs">
            <div class="card-header bg-light border-bottom py-3">
                <h5 class="card-title mb-0 fw-bold fs-6 text-dark">
                    <i class="mdi mdi-book-open-page-variant-outline text-dark me-2"></i>Rule Overview
                </h5>
            </div>
            <div class="card-body pt-3">
                <div class="detail-row py-2 border-bottom d-flex align-items-center justify-content-between">
                    <span class="text-muted small"><i class="mdi mdi-trophy-outline me-1"></i>Tournament:</span>
                    <span class="fw-semibold text-end">
                        @if($tourn)
                            <a href="{{ route('admin.tournaments.show', $tourn) }}" class="rule-page-black-link">{{ $tourn->name }}</a>
                        @else
                            —
                        @endif
                    </span>
                </div>
                <div class="detail-row py-2 border-bottom d-flex align-items-center justify-content-between">
                    <span class="text-muted small"><i class="mdi mdi-shape-outline me-1"></i>Format:</span>
                    <span class="badge bg-label-info fw-semibold">{{ ucfirst(($tournamentRule?->tournament_format ?: $tourn?->format) ?: 'Standard') }}</span>
                </div>
                <div class="detail-row py-2 border-bottom d-flex align-items-center justify-content-between">
                    <span class="text-muted small"><i class="mdi mdi-domain me-1"></i>Host Club:</span>
                    <span class="fw-bold text-dark text-end">{{ $club?->club_name ?? $club?->name ?? '—' }}</span>
                </div>
                <div class="detail-row py-2 border-bottom d-flex align-items-center justify-content-between">
                    <span class="text-muted small"><i class="mdi mdi-calendar-clock me-1"></i>Configured Date:</span>
                    <span class="text-dark fw-medium small">{{ $tournamentRule?->created_at ? $tournamentRule->created_at->format('Y-m-d H:i') : '—' }}</span>
                </div>
                @if($tournamentRule?->note)
                    <div class="mt-3 p-3 bg-label-secondary rounded border border-dashed">
                        <small class="fw-bold d-block text-uppercase mb-1 text-muted" style="letter-spacing: 0.5px;">Additional Note:</small>
                        <p class="mb-0 small text-dark" style="white-space: pre-wrap;">{{ $tournamentRule->note }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Structured Rules Sections -->
    <div class="col-md-8 mb-4">
        @if($tournamentRule)
            <div class="card mb-3 border shadow-xs">
                <div class="card-header border-bottom bg-light py-3">
                    <h5 class="card-title mb-0 fw-bold fs-6 text-dark"><i class="mdi mdi-cogs text-dark me-2"></i>Competition Setup & Format Rules</h5>
                </div>
                <div class="card-body pt-3">
                    @include('admin.components.rule-viewer', ['rules' => $tournamentRule->competition_setup])
                </div>
            </div>

            @if($tournamentRule->pool_rules)
                <div class="card mb-3 border shadow-xs">
                    <div class="card-header border-bottom bg-light py-3">
                        <h5 class="card-title mb-0 fw-bold fs-6 text-dark"><i class="mdi mdi-format-list-checks text-dark me-2"></i>Pool / Group Stage Rules</h5>
                    </div>
                    <div class="card-body pt-3">
                        @include('admin.components.rule-viewer', ['rules' => $tournamentRule->pool_rules])
                    </div>
                </div>
            @endif

            @if($tournamentRule->knockout_rounds)
                <div class="card mb-3 border shadow-xs">
                    <div class="card-header border-bottom bg-light py-3">
                        <h5 class="card-title mb-0 fw-bold fs-6 text-dark"><i class="mdi mdi-tournament text-dark me-2"></i>Knockout Round Rules</h5>
                    </div>
                    <div class="card-body pt-3">
                        @include('admin.components.rule-viewer', ['rules' => $tournamentRule->knockout_rounds])
                    </div>
                </div>
            @endif

            @if($tournamentRule->match_equipment)
                <div class="card mb-3 border shadow-xs">
                    <div class="card-header border-bottom bg-light py-3">
                        <h5 class="card-title mb-0 fw-bold fs-6 text-dark"><i class="mdi mdi-tennis text-dark me-2"></i>Match & Equipment Rules</h5>
                    </div>
                    <div class="card-body pt-3">
                        @include('admin.components.rule-viewer', ['rules' => $tournamentRule->match_equipment])
                    </div>
                </div>
            @endif

            @if($tournamentRule->scoring_rules)
                <div class="card mb-3 border shadow-xs">
                    <div class="card-header border-bottom bg-light py-3">
                        <h5 class="card-title mb-0 fw-bold fs-6 text-dark"><i class="mdi mdi-scoreboard-outline text-dark me-2"></i>Scoring System Rules</h5>
                    </div>
                    <div class="card-body pt-3">
                        @include('admin.components.rule-viewer', ['rules' => $tournamentRule->scoring_rules])
                    </div>
                </div>
            @endif
        @else
            <div class="card h-100 border shadow-xs">
                <div class="card-body text-center py-5">
                    <div class="mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center bg-light border"
                         style="width:72px; height:72px;">
                        <i class="mdi mdi-cogs text-dark" style="font-size: 32px;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">No Custom Rules Configured</h5>
                    <p class="text-muted mb-0 px-md-5" style="max-width: 480px; margin: 0 auto;">
                        The host club has not configured custom rules for <strong>{{ $tourn?->name ?? 'this tournament' }}</strong> yet. Standard tournament defaults apply.
                    </p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@extends('layouts/contentNavbarLayout')

@section('title', 'Tournament Rules Details')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            <a href="{{ route('admin.tournament-rules.index') }}" class="text-muted fw-normal me-2">
                <i class="mdi mdi-arrow-left"></i> Tournament Rules /
            </a>
            Rules for {{ $tournamentRule->tournament?->name ?? ('Tournament #' . $tournamentRule->tournament_id) }}
        </h4>
        <span class="text-muted">Host Club: <strong>{{ $tournamentRule->club?->club_name ?? $tournamentRule->club?->name ?? '—' }}</strong></span>
    </div>
    <div>
        <a href="{{ route('admin.tournament-rules.index') }}" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left me-1"></i> Back to Rules
        </a>
    </div>
</div>

<div class="row">
    <!-- Overview & Basic Details -->
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Rule Overview</h5>
            </div>
            <div class="card-body pt-3">
                <div class="row mb-2">
                    <div class="col-5 text-muted">Tournament:</div>
                    <div class="col-7 fw-semibold">
                        @if($tournamentRule->tournament)
                            <a href="{{ route('admin.tournaments.show', $tournamentRule->tournament) }}">{{ $tournamentRule->tournament->name }}</a>
                        @else
                            #{{ $tournamentRule->tournament_id }}
                        @endif
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted">Format:</div>
                    <div class="col-7">
                        <span class="badge bg-label-primary">{{ ucfirst($tournamentRule->tournament_format ?: 'Standard') }}</span>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted">Club:</div>
                    <div class="col-7 fw-semibold">{{ $tournamentRule->club?->club_name ?? $tournamentRule->club?->name ?? '—' }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted">Created At:</div>
                    <div class="col-7">{{ $tournamentRule->created_at ? $tournamentRule->created_at->format('Y-m-d H:i') : '—' }}</div>
                </div>
                @if($tournamentRule->note)
                    <div class="mt-3 p-3 bg-label-secondary rounded">
                        <small class="fw-bold d-block text-uppercase mb-1 text-muted">Additional Note:</small>
                        <p class="mb-0 small text-dark">{{ $tournamentRule->note }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Structured Rules Sections -->
    <div class="col-md-8 mb-4">
        <div class="card mb-3">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0"><i class="mdi mdi-cogs me-2"></i>Competition Setup & Format Rules</h5>
            </div>
            <div class="card-body pt-3">
                @include('admin.components.rule-viewer', ['rules' => $tournamentRule->competition_setup])
            </div>
        </div>

        @if($tournamentRule->pool_rules)
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0"><i class="mdi mdi-format-list-checks me-2"></i>Pool / Group Stage Rules</h5>
                </div>
                <div class="card-body pt-3">
                    @include('admin.components.rule-viewer', ['rules' => $tournamentRule->pool_rules])
                </div>
            </div>
        @endif

        @if($tournamentRule->knockout_rounds)
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0"><i class="mdi mdi-tournament me-2"></i>Knockout Round Rules</h5>
                </div>
                <div class="card-body pt-3">
                    @include('admin.components.rule-viewer', ['rules' => $tournamentRule->knockout_rounds])
                </div>
            </div>
        @endif

        @if($tournamentRule->match_equipment)
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0"><i class="mdi mdi-tennis me-2"></i>Match & Equipment Rules</h5>
                </div>
                <div class="card-body pt-3">
                    @include('admin.components.rule-viewer', ['rules' => $tournamentRule->match_equipment])
                </div>
            </div>
        @endif

        @if($tournamentRule->scoring_rules)
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0"><i class="mdi mdi-scoreboard-outline me-2"></i>Scoring System Rules</h5>
                </div>
                <div class="card-body pt-3">
                    @include('admin.components.rule-viewer', ['rules' => $tournamentRule->scoring_rules])
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

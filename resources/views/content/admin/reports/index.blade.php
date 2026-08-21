@extends('layouts/contentNavbarLayout')

@section('title', 'System Reports')

@section('content')
<div class="admin-page reports-page">
    <div class="admin-page-header mb-4">
        <h4 class="admin-page-header__title">System Reports</h4>
        <p class="admin-page-header__subtitle">Real-time dynamic analytics and aggregates for clubs, players, bookings, and tournaments.</p>
    </div>

    <!-- Stats row 1: Club and Player Analytics -->
    <div class="row g-4 mb-4">
        <!-- Club Reports -->
        <div class="col-12 col-md-6">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-transparent border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="mdi mdi-domain text-primary" style="font-size: 24px;"></i> Club Reports
                    </h5>
                    <span class="badge bg-label-primary">Clubs Registry</span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="bg-light p-3 rounded text-center">
                                <span class="text-muted small d-block mb-1">Total Clubs</span>
                                <h3 class="fw-bold text-dark mb-0">{{ number_format($clubStats['total']) }}</h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light p-3 rounded text-center">
                                <span class="text-muted small d-block mb-1">Active Clubs</span>
                                <h3 class="fw-bold text-success mb-0">{{ number_format($clubStats['active']) }}</h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light p-3 rounded text-center">
                                <span class="text-muted small d-block mb-1">Total Courts Owned</span>
                                <h3 class="fw-bold text-dark mb-0">{{ number_format($clubStats['total_courts']) }}</h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light p-3 rounded text-center">
                                <span class="text-muted small d-block mb-1">Average Club Rating</span>
                                <h3 class="fw-bold text-warning mb-0">
                                    <i class="mdi mdi-star"></i> {{ $clubStats['average_rating'] }}
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Player Reports -->
        <div class="col-12 col-md-6">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-transparent border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="mdi mdi-account-group text-success" style="font-size: 24px;"></i> Player Demographics
                    </h5>
                    <span class="badge bg-label-success">Player Roster</span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <div class="bg-light p-3 rounded text-center">
                                <span class="text-muted small d-block mb-1">Total Players</span>
                                <h3 class="fw-bold text-dark mb-0">{{ number_format($playerStats['total']) }}</h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light p-3 rounded text-center">
                                <span class="text-muted small d-block mb-1">Active Players</span>
                                <h3 class="fw-bold text-success mb-0">{{ number_format($playerStats['active']) }}</h3>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Level stats -->
                    <div class="mb-3">
                        <span class="text-muted small fw-semibold d-block mb-2">Playing Levels Distribution</span>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-label-secondary">Beginner: {{ $playerStats['levels']['beginner'] }}</span>
                            <span class="badge bg-label-info">Intermediate: {{ $playerStats['levels']['intermediate'] }}</span>
                            <span class="badge bg-label-warning">Advanced: {{ $playerStats['levels']['advanced'] }}</span>
                            <span class="badge bg-label-primary">Professional: {{ $playerStats['levels']['professional'] }}</span>
                        </div>
                    </div>

                    <!-- Gender distribution -->
                    <div>
                        <span class="text-muted small fw-semibold d-block mb-2">Gender Split</span>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-label-primary"><i class="mdi mdi-gender-male"></i> Male: {{ $playerStats['genders']['male'] }}</span>
                            <span class="badge bg-label-danger"><i class="mdi mdi-gender-female"></i> Female: {{ $playerStats['genders']['female'] }}</span>
                            <span class="badge bg-label-secondary"><i class="mdi mdi-gender-transgender"></i> Mixed/Other: {{ $playerStats['genders']['other'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats row 2: Booking and Tournament Analytics -->
    <div class="row g-4">
        <!-- Booking & Financials -->
        <div class="col-12 col-md-6">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-transparent border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="mdi mdi-calendar-check text-warning" style="font-size: 24px;"></i> Booking & Financials
                    </h5>
                    <span class="badge bg-label-warning">Finance</span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="bg-light p-3 rounded text-center">
                                <span class="text-muted small d-block mb-1">Total Bookings</span>
                                <h3 class="fw-bold text-dark mb-0">{{ number_format($bookingStats['total']) }}</h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light p-3 rounded text-center">
                                <span class="text-muted small d-block mb-1">Total Revenue</span>
                                <h3 class="fw-bold text-success mb-0">PKR {{ number_format($bookingStats['total_revenue'], 0) }}</h3>
                            </div>
                        </div>
                    </div>

                    <!-- Booking statuses progress -->
                    <span class="text-muted small fw-semibold d-block mb-2">Booking Statuses Breakdown</span>
                    <div class="row g-2">
                        <div class="col-6 col-md-3">
                            <div class="border p-2 rounded text-center">
                                <small class="text-muted d-block">Pending</small>
                                <strong class="text-warning">{{ $bookingStats['statuses']['pending'] }}</strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border p-2 rounded text-center">
                                <small class="text-muted d-block">Confirmed</small>
                                <strong class="text-success">{{ $bookingStats['statuses']['confirmed'] }}</strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border p-2 rounded text-center">
                                <small class="text-muted d-block">Completed</small>
                                <strong class="text-primary">{{ $bookingStats['statuses']['completed'] }}</strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border p-2 rounded text-center">
                                <small class="text-muted d-block">Cancelled</small>
                                <strong class="text-danger">{{ $bookingStats['statuses']['cancelled'] }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tournament Analytics -->
        <div class="col-12 col-md-6">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-transparent border-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="mdi mdi-trophy-variant text-danger" style="font-size: 24px;"></i> Tournament Analytics
                    </h5>
                    <span class="badge bg-label-danger">Tournaments</span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="bg-light p-3 rounded text-center">
                                <span class="text-muted small d-block mb-1">Total Tournaments</span>
                                <h3 class="fw-bold text-dark mb-0">{{ number_format($tournamentStats['total']) }}</h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light p-3 rounded text-center">
                                <span class="text-muted small d-block mb-1">Total Enrolled Players</span>
                                <h3 class="fw-bold text-primary mb-0">{{ number_format($tournamentStats['total_registrations']) }}</h3>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <span class="text-muted small fw-semibold d-block mb-2">Tournament Types Split</span>
                            <div class="d-flex flex-column gap-1">
                                <small class="text-muted">Club-to-Club: <strong>{{ $tournamentStats['types']['club_to_club'] }}</strong></small>
                                <small class="text-muted">Members-Only: <strong>{{ $tournamentStats['types']['members_only'] }}</strong></small>
                            </div>
                        </div>
                        <div class="col-6 text-end">
                            <span class="text-muted small fw-semibold d-block mb-2 text-end">Total Prize Pools</span>
                            <h4 class="fw-bold text-dark mb-0">PKR {{ number_format($tournamentStats['total_prize_pool'], 0) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

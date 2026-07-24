<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Booking;
use App\Models\Tournament;
use App\Models\Court;
use App\Models\TournamentRegistration;
use App\Models\BookingReview;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        // 1. Club Stats
        $clubStats = [
            'total' => User::where('role', 'club')->count(),
            'active' => User::where('role', 'club')->where('status', 'active')->count(),
            'total_courts' => Court::count(),
            'average_rating' => round((float) BookingReview::avg('rating') ?: 0, 1),
        ];

        // 2. Player Stats
        $playerStats = [
            'total' => User::where('role', 'player')->count(),
            'active' => User::where('role', 'player')->where('status', 'active')->count(),
            'levels' => [
                'beginner' => User::where('role', 'player')->where('playing_level', 'beginner')->count(),
                'intermediate' => User::where('role', 'player')->where('playing_level', 'intermediate')->count(),
                'advanced' => User::where('role', 'player')->where('playing_level', 'advanced')->count(),
                'professional' => User::where('role', 'player')->where('playing_level', 'professional')->count(),
            ],
            'genders' => [
                'male' => User::where('role', 'player')->where('gender', 'male')->count(),
                'female' => User::where('role', 'player')->where('gender', 'female')->count(),
                'other' => User::where('role', 'player')->whereNull('gender')->count(),
            ]
        ];

        // 3. Booking Stats
        $bookingStats = [
            'total' => Booking::count(),
            'statuses' => [
                'pending' => Booking::where('booking_status', 'pending')->count(),
                'confirmed' => Booking::where('booking_status', 'confirmed')->count(),
                'completed' => Booking::where('booking_status', 'completed')->count(),
                'cancelled' => Booking::where('booking_status', 'cancelled')->count(),
            ],
            'total_revenue' => (float) Booking::whereIn('booking_status', ['confirmed', 'completed'])->sum('total_amount'),
            'average_amount' => round((float) Booking::avg('total_amount') ?: 0, 2),
        ];

        // 4. Tournament Stats
        $tournamentStats = [
            'total' => Tournament::count(),
            'types' => [
                'club_to_club' => Tournament::where('tournament_type', 'CLUB_TO_CLUB')->count(),
                'members_only' => Tournament::where('tournament_type', 'CLUB_MEMBERS_ONLY')->count(),
            ],
            'total_registrations' => TournamentRegistration::where('registration_status', 'registered')->count(),
            'total_prize_pool' => (float) Tournament::sum('prize_pool'),
        ];

        return view('content.admin.reports.index', compact(
            'clubStats',
            'playerStats',
            'bookingStats',
            'tournamentStats'
        ));
    }
}

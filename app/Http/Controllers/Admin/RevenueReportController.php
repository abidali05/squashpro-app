<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\TournamentRegistration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RevenueReportController extends Controller
{
    public function index(Request $request): View
    {
        $year = $request->integer('year', Carbon::now()->year);

        // Core aggregates
        $bookingRevenue = (float) Booking::where('payment_status', 'paid')->sum('total_amount');
        $tournamentRevenue = (float) TournamentRegistration::where('payment_status', 'paid')->sum('amount');
        $totalRevenue = $bookingRevenue + $tournamentRevenue;

        // Group by Month for selected year (database-agnostic Collection grouping)
        $bookings = Booking::whereYear('created_at', $year)
            ->where('payment_status', 'paid')
            ->get();

        $tournaments = TournamentRegistration::whereYear('created_at', $year)
            ->where('payment_status', 'paid')
            ->get();

        $monthlyChartData = [];
        for ($m = 1; $m <= 12; $m++) {
            $bAmt = (float) $bookings->filter(fn ($b) => $b->created_at?->month === $m)->sum('total_amount');
            $tAmt = (float) $tournaments->filter(fn ($t) => $t->created_at?->month === $m)->sum('amount');
            $monthlyChartData[] = [
                'month' => Carbon::create()->month($m)->format('F'),
                'bookings' => $bAmt,
                'tournaments' => $tAmt,
                'total' => $bAmt + $tAmt,
            ];
        }

        // Payment Method breakdown
        $allBookings = Booking::where('payment_status', 'paid')->get();
        $bookingMethods = $allBookings->groupBy('payment_method')->map(fn ($group) => [
            'method' => $group->first()->payment_method,
            'count' => $group->count(),
            'revenue' => (float) $group->sum('total_amount'),
        ])->values();

        $allTournaments = TournamentRegistration::where('payment_status', 'paid')->get();
        $tournamentMethods = $allTournaments->groupBy('payment_method_id')->map(fn ($group) => [
            'method' => $group->first()->payment_method_id,
            'count' => $group->count(),
            'revenue' => (float) $group->sum('amount'),
        ])->values();

        return view('content.admin.reports.revenue', compact(
            'year',
            'bookingRevenue',
            'tournamentRevenue',
            'totalRevenue',
            'monthlyChartData',
            'bookingMethods',
            'tournamentMethods'
        ));
    }
}

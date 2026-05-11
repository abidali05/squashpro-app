<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Dashboard Statistics (Dummy Data - Easy to replace with real queries later)
        $dashboardStats = [
            [
                'title' => 'Total Users',
                'value' => '1,456',
                'icon' => 'mdi mdi-account-multiple',
                'iconClass' => 'icon-primary'
            ],
            [
                'title' => 'Active Users',
                'value' => '892',
                'icon' => 'mdi mdi-account-check',
                'iconClass' => 'icon-success'
            ],
            [
                'title' => 'Inactive Users',
                'value' => '564',
                'icon' => 'mdi mdi-account-off',
                'iconClass' => 'icon-danger'
            ],
            [
                'title' => 'Total Clubs',
                'value' => '48',
                'icon' => 'mdi mdi-domain',
                'iconClass' => 'icon-info',
                'link' => route('admin.clubs.index')
            ],
            [
                'title' => 'Active Clubs',
                'value' => '42',
                'icon' => 'mdi mdi-check-circle',
                'iconClass' => 'icon-success',
                'link' => route('admin.clubs.index')
            ],
            [
                'title' => 'Pending Approvals',
                'value' => '6',
                'icon' => 'mdi mdi-clock-outline',
                'iconClass' => 'icon-warning',
                'link' => route('admin.clubs.index')
            ],
            [
                'title' => 'Total Players',
                'value' => '1,248',
                'icon' => 'mdi mdi-account-group',
                'iconClass' => 'icon-purple',
                'link' => route('admin.players.index')
            ],
            [
                'title' => 'Active Players',
                'value' => '892',
                'icon' => 'mdi mdi-account-check',
                'iconClass' => 'icon-success',
                'link' => route('admin.players.index')
            ],
            [
                'title' => 'Bookings Today',
                'value' => '34',
                'icon' => 'mdi mdi-calendar-today',
                'iconClass' => 'icon-accent',
                'link' => route('admin.bookings.index')
            ],
            [
                'title' => 'Active Bookings',
                'value' => '128',
                'icon' => 'mdi mdi-calendar-check',
                'iconClass' => 'icon-success',
                'link' => route('admin.bookings.index')
            ],
            [
                'title' => 'Cancelled Bookings',
                'value' => '12',
                'icon' => 'mdi mdi-calendar-remove',
                'iconClass' => 'icon-danger',
                'link' => route('admin.bookings.index')
            ],
            [
                'title' => 'Total Tournaments',
                'value' => '24',
                'icon' => 'mdi mdi-trophy',
                'iconClass' => 'icon-warning',
                'link' => route('admin.tournaments.index')
            ],
            [
                'title' => 'Active Tournaments',
                'value' => '8',
                'icon' => 'mdi mdi-trophy-variant',
                'iconClass' => 'icon-accent',
                'link' => route('admin.tournaments.index')
            ],
            [
                'title' => 'Total Revenue',
                'value' => '$48.2K',
                'icon' => 'mdi mdi-currency-usd',
                'iconClass' => 'icon-success'
            ],
            [
                'title' => 'Pending Payments',
                'value' => '$3.8K',
                'icon' => 'mdi mdi-clock-alert-outline',
                'iconClass' => 'icon-warning'
            ],
            [
                'title' => 'Refund Requests',
                'value' => '5',
                'icon' => 'mdi mdi-cash-refund',
                'iconClass' => 'icon-danger'
            ]
        ];

        // Chart Data (Dummy Data - Replace with real queries later)
        $chartData = [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'bookings' => [45, 52, 48, 65, 72, 68, 85, 92, 88, 95, 102, 110],
            'revenue' => [12, 15, 14, 18, 22, 20, 25, 28, 26, 30, 34, 38],
            'players' => [120, 145, 168, 192, 215, 238, 265, 288, 312, 340, 368, 395],
            'clubs' => [8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30]
        ];

        return view('content.admin.dashboard.index', compact('dashboardStats', 'chartData'));
    }
}

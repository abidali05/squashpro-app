<?php

use App\Http\Controllers\Admin\ClubController;
use App\Http\Controllers\Admin\BookingManagementController;
use App\Http\Controllers\Admin\CourtController;
use App\Http\Controllers\Admin\CourtManagementController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ModulePlaceholderController;
use App\Http\Controllers\Admin\PrivacyPolicyController;
use App\Http\Controllers\Admin\SupportOptionController;
use App\Http\Controllers\Admin\TournamentManagementController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PlayerController;
use App\Http\Controllers\Admin\MembershipManagementController;
use App\Http\Controllers\Admin\TournamentRegistrationController;
use App\Http\Controllers\Admin\TournamentRuleManagementController;
use App\Http\Controllers\Admin\TournamentPoolManagementController;
use App\Http\Controllers\Admin\FixtureManagementController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BookingReviewManagementController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\NotificationManagementController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\RevenueReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserRoleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:super_admin|admin'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function (): void {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

        // Clubs CRUD + nested Courts
        Route::get('clubs', [ClubController::class, 'index'])->name('clubs.index');
        Route::get('clubs/create', [ClubController::class, 'create'])->name('clubs.create');
        Route::post('clubs', [ClubController::class, 'store'])->name('clubs.store');
        Route::get('clubs/{club}', [ClubController::class, 'show'])->name('clubs.show');
        Route::get('clubs/{club}/edit', [ClubController::class, 'edit'])->name('clubs.edit');
        Route::put('clubs/{club}', [ClubController::class, 'update'])->name('clubs.update');
        Route::patch('clubs/{club}/status', [ClubController::class, 'updateStatus'])->name('clubs.status');
        Route::delete('clubs/{club}', [ClubController::class, 'destroy'])->name('clubs.destroy');

        // Courts (nested under clubs)
        Route::get('clubs/{club}/courts/create', [CourtController::class, 'create'])->name('clubs.courts.create');
        Route::post('clubs/{club}/courts', [CourtController::class, 'store'])->name('clubs.courts.store');
        Route::get('clubs/{club}/courts/{court}/edit', [CourtController::class, 'edit'])->name('clubs.courts.edit');
        Route::put('clubs/{club}/courts/{court}', [CourtController::class, 'update'])->name('clubs.courts.update');
        Route::delete('clubs/{club}/courts/{court}', [CourtController::class, 'destroy'])->name('clubs.courts.destroy');

        // Players CRUD
        Route::get('players', [PlayerController::class, 'index'])->name('players.index');
        Route::get('players/create', [PlayerController::class, 'create'])->name('players.create');
        Route::post('players', [PlayerController::class, 'store'])->name('players.store');
        Route::get('players/{player}', [PlayerController::class, 'show'])->name('players.show');
        Route::get('players/{player}/edit', [PlayerController::class, 'edit'])->name('players.edit');
        Route::put('players/{player}', [PlayerController::class, 'update'])->name('players.update');
        Route::patch('players/{player}/status', [PlayerController::class, 'updateStatus'])->name('players.status');
        Route::delete('players/{player}', [PlayerController::class, 'destroy'])->name('players.destroy');

        Route::get('bookings', [BookingManagementController::class, 'index'])->name('bookings.index');
        Route::get('bookings/{booking}', [BookingManagementController::class, 'show'])->name('bookings.show');
        Route::post('bookings/{booking}/status', [BookingManagementController::class, 'updateStatus'])->name('bookings.status');
        Route::get('booking-reviews', [BookingReviewManagementController::class, 'index'])->name('booking-reviews.index');
        Route::delete('booking-reviews/{review}', [BookingReviewManagementController::class, 'destroy'])->name('booking-reviews.destroy');

        // Club Memberships
        Route::get('memberships', [MembershipManagementController::class, 'index'])->name('memberships.index');
        Route::delete('memberships/{membership}', [MembershipManagementController::class, 'destroy'])->name('memberships.destroy');
        Route::get('membership-requests', [MembershipManagementController::class, 'requestsIndex'])->name('membership-requests.index');
        Route::get('courts', [CourtManagementController::class, 'index'])->name('courts.index');
        Route::get('courts/create', [CourtManagementController::class, 'create'])->name('courts.create');
        Route::post('courts', [CourtManagementController::class, 'store'])->name('courts.store');
        Route::get('courts/{court}', [CourtManagementController::class, 'show'])->name('courts.show');
        Route::get('courts/{court}/edit', [CourtManagementController::class, 'edit'])->name('courts.edit');
        Route::put('courts/{court}', [CourtManagementController::class, 'update'])->name('courts.update');
        Route::delete('courts/{court}', [CourtManagementController::class, 'destroy'])->name('courts.destroy');
        Route::get('tournaments', [TournamentManagementController::class, 'index'])->name('tournaments.index');
        Route::get('tournaments/create', [TournamentManagementController::class, 'create'])->name('tournaments.create');
        Route::post('tournaments', [TournamentManagementController::class, 'store'])->name('tournaments.store');
        Route::get('tournaments/{tournament}', [TournamentManagementController::class, 'show'])->name('tournaments.show');
        Route::get('tournaments/{tournament}/edit', [TournamentManagementController::class, 'edit'])->name('tournaments.edit');
        Route::put('tournaments/{tournament}', [TournamentManagementController::class, 'update'])->name('tournaments.update');
        Route::delete('tournaments/{tournament}', [TournamentManagementController::class, 'destroy'])->name('tournaments.destroy');
        Route::post('tournaments/{tournament}/status', [TournamentManagementController::class, 'updateStatus'])->name('tournaments.status');

        // Tournament Registrations
        Route::get('tournament-registrations', [TournamentRegistrationController::class, 'index'])->name('tournament-registrations.index');
        Route::post('tournament-registrations/{registration}/approve', [TournamentRegistrationController::class, 'approve'])->name('tournament-registrations.approve');
        Route::delete('tournament-registrations/{registration}', [TournamentRegistrationController::class, 'destroy'])->name('tournament-registrations.destroy');

        // Tournament Rules Management
        Route::get('tournament-rules', [TournamentRuleManagementController::class, 'index'])->name('tournament-rules.index');
        Route::get('tournament-rules/{tournamentRule}', [TournamentRuleManagementController::class, 'show'])->name('tournament-rules.show');
        Route::delete('tournament-rules/{tournamentRule}', [TournamentRuleManagementController::class, 'destroy'])->name('tournament-rules.destroy');

        // Tournament Pools Management
        Route::get('tournament-pools', [TournamentPoolManagementController::class, 'index'])->name('tournament-pools.index');
        Route::get('tournament-pools/{tournamentPool}', [TournamentPoolManagementController::class, 'show'])->name('tournament-pools.show');
        Route::delete('tournament-pools/{tournamentPool}', [TournamentPoolManagementController::class, 'destroy'])->name('tournament-pools.destroy');

        // Fixtures Management
        Route::get('fixtures', [FixtureManagementController::class, 'index'])->name('fixtures.index');
        Route::get('fixtures/{fixture}', [FixtureManagementController::class, 'show'])->name('fixtures.show');
        Route::delete('fixtures/{fixture}', [FixtureManagementController::class, 'destroy'])->name('fixtures.destroy');
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('revenue-reports', [RevenueReportController::class, 'index'])->name('revenue.index');
        Route::get('notifications', [NotificationManagementController::class, 'index'])->name('notifications.index');
        Route::delete('notifications/{notification}', [NotificationManagementController::class, 'destroy'])->name('notifications.destroy');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');

        Route::resource('support-options', SupportOptionController::class)->except(['show']);
        Route::get('privacy-policy', [PrivacyPolicyController::class, 'edit'])->name('privacy-policy.edit');
        Route::put('privacy-policy', [PrivacyPolicyController::class, 'update'])->name('privacy-policy.update');

        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('permissions/create', [PermissionController::class, 'create'])->name('permissions.create');
        Route::post('permissions', [PermissionController::class, 'store'])->name('permissions.store');
        Route::get('permissions/{permission}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
        Route::put('permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
        Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

        Route::get('users', [UserRoleController::class, 'index'])->name('users.index');
        Route::get('users/{user}/roles', [UserRoleController::class, 'edit'])->name('users.roles.edit');
        Route::put('users/{user}/roles', [UserRoleController::class, 'update'])->name('users.roles.update');

        // Audit Logs
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });

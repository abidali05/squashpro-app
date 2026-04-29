<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ModulePlaceholderController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserRoleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:super_admin|admin'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function (): void {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

        Route::get('clubs', [ModulePlaceholderController::class, 'index'])->defaults('module', 'clubs')->name('clubs.index');
        Route::get('players', [ModulePlaceholderController::class, 'index'])->defaults('module', 'players')->name('players.index');
        Route::get('bookings', [ModulePlaceholderController::class, 'index'])->defaults('module', 'bookings')->name('bookings.index');
        Route::get('courts', [ModulePlaceholderController::class, 'index'])->defaults('module', 'courts')->name('courts.index');
        Route::get('tournaments', [ModulePlaceholderController::class, 'index'])->defaults('module', 'tournaments')->name('tournaments.index');
        Route::get('payments', [ModulePlaceholderController::class, 'index'])->defaults('module', 'payments')->name('payments.index');
        Route::get('revenue-reports', [ModulePlaceholderController::class, 'index'])->defaults('module', 'revenue reports')->name('revenue.index');
        Route::get('notifications', [ModulePlaceholderController::class, 'index'])->defaults('module', 'notifications')->name('notifications.index');
        Route::get('reports', [ModulePlaceholderController::class, 'index'])->defaults('module', 'reports')->name('reports.index');
        Route::get('settings', [ModulePlaceholderController::class, 'index'])->defaults('module', 'settings')->name('settings.index');

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
    });

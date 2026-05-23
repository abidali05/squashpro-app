<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClubController;
use App\Http\Controllers\Api\V1\PlayerBookingController;
use App\Http\Controllers\Api\V1\PlayerClubController;
use App\Http\Controllers\Api\V1\PublicCityController;
use Illuminate\Support\Facades\Route;


// API Version 1
Route::prefix('v1')->group(function () {

    Route::get('/cities', [PublicCityController::class, 'index']);

    // Public Auth Routes (No Authentication Required)
    Route::prefix('auth')->group(function () {
        // Registration
        Route::post('register/player', [AuthController::class, 'registerPlayer']);
        Route::post('register/club', [AuthController::class, 'registerClub']);

        // OTP Verification
        Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
        Route::post('resend-otp', [AuthController::class, 'resendOtp']);

        // Login
        Route::post('login', [AuthController::class, 'login']);

        // Password Reset
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('forgot-password/verify-otp', [AuthController::class, 'verifyForgotPasswordOtp']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
    });

    // Protected Routes (Authentication Required)
    Route::middleware('api.token')->group(function () {

        // Player Routes
        Route::prefix('player')->middleware('api.role:player')->group(function () {
            Route::post('complete-profile', [AuthController::class, 'completePlayerProfile']);
            Route::get('clubs', [PlayerClubController::class, 'index']);
            Route::get('clubs/{club_id}', [PlayerClubController::class, 'show']);
            Route::get('clubs/{club_id}/courts', [PlayerClubController::class, 'courts']);
            Route::get('courts/{court_id}/time-slots', [PlayerClubController::class, 'timeSlots']);
            Route::post('bookings', [PlayerBookingController::class, 'store']);
            // Add more player-specific routes here
            // Route::get('profile', [PlayerController::class, 'getProfile']);
            // Route::put('profile', [PlayerController::class, 'updateProfile']);
            // Route::get('bookings', [PlayerBookingController::class, 'index']);
        });

        // Club Routes
        Route::prefix('club')->middleware('api.role:club')->group(function () {
            Route::get('dashboard', [ClubController::class, 'dashboard']);
            Route::get('courts', [ClubController::class, 'courts']);
            Route::post('courts', [ClubController::class, 'storeCourt']);
            Route::get('courts/{court_id}', [ClubController::class, 'courtDetail']);
            Route::post('courts/{court_id}/edit', [ClubController::class, 'editCourt']);
            Route::post('courts/{court_id}/set-maintenance', [ClubController::class, 'setCourtMaintenance']);
            Route::get('tournaments', [ClubController::class, 'tournaments']);
            Route::post('tournaments', [ClubController::class, 'storeTournament']);
            Route::get('tournaments/{tournament_id}', [ClubController::class, 'tournamentDetail']);
            Route::post('tournaments/{tournament_id}/update', [ClubController::class, 'updateTournament']);
            Route::get('profile', [ClubController::class, 'profile']);
            Route::post('details/update', [ClubController::class, 'updateClubDetails']);
            // Add club-specific routes here
            // Route::get('profile', [ClubController::class, 'getProfile']);
            // Route::put('profile', [ClubController::class, 'updateProfile']);
            // Route::get('courts', [ClubCourtController::class, 'index']);
            // Route::post('courts', [ClubCourtController::class, 'store']);
        });

        // Common Authenticated Routes (Both Player & Club)
        Route::prefix('account')->group(function () {
            // Route::get('me', [AuthController::class, 'getAuthenticatedUser']);
            // Route::post('logout', [AuthController::class, 'logout']);
            // Route::post('refresh-token', [AuthController::class, 'refreshToken']);
            // Route::put('change-password', [AuthController::class, 'changePassword']);
        });
    });
});

<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

// API Version 1
Route::prefix('v1')->group(function () {
    
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
            // Add more player-specific routes here
            // Route::get('profile', [PlayerController::class, 'getProfile']);
            // Route::put('profile', [PlayerController::class, 'updateProfile']);
            // Route::get('bookings', [PlayerBookingController::class, 'index']);
        });
        
        // Club Routes
        Route::prefix('club')->middleware('api.role:club')->group(function () {
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

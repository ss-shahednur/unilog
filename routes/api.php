<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerifyController;
use App\Http\Controllers\Auth\ResendOtpController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Profile\ProfileController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Helper function to define customer routes (DRY)
$defineCustomerRoutes = function ($prefix = '') {
    Route::prefix($prefix . 'customers')->group(function () {
        // Public authentication routes
        Route::post('register', [RegisterController::class, 'store'])
            ->middleware('throttle:auth_register');

        Route::post('verify-register-otp', [VerifyController::class, 'verifyRegisterOtp'])
            ->middleware('throttle:otp_verify');

        Route::post('resend-otp', [ResendOtpController::class, 'resend'])
            ->middleware('throttle:otp_resend');

        Route::post('login', [LoginController::class, 'login'])
            ->middleware('throttle:auth_login');

        Route::post('forgot-password', [ForgotPasswordController::class, 'sendOtp'])
            ->middleware('throttle:auth_forgot');

        Route::post('reset-password', [ResetPasswordController::class, 'reset'])
            ->middleware('throttle:auth_reset');

        // Protected routes (require authentication)
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('change-password', [ChangePasswordController::class, 'change']);
            Route::post('update-profile/{id}', [ProfileController::class, 'update']);
        });
    });
};

// Define routes under /customers
$defineCustomerRoutes('');

// Define routes under /api/customers (alias for mobile compatibility)
// $defineCustomerRoutes('api/');
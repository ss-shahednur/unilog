<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure rate limiting for authentication and OTP operations.
     */
    protected function configureRateLimiting(): void
    {
        // OTP Resend: 1 per minute + 5 per hour per email+IP
        RateLimiter::for('otp_resend', function (Request $request) {
            $key = $request->ip() . '|' . $request->input('email');

            return [
                Limit::perMinute(1)->by($key)->response(function () {
                    return response()->json([
                        'message' => 'Too many OTP requests. Please wait 1 minute.',
                    ], 429);
                }),
                Limit::perHour(5)->by($key)->response(function () {
                    return response()->json([
                        'message' => 'Too many OTP requests. Please try again later.',
                    ], 429);
                }),
            ];
        });

        // OTP Verify: 10 per minute per email+IP (attempts tracked in DB)
        RateLimiter::for('otp_verify', function (Request $request) {
            $key = $request->ip() . '|' . $request->input('email');

            return Limit::perMinute(10)->by($key)->response(function () {
                return response()->json([
                    'message' => 'Too many verification attempts. Please wait.',
                ], 429);
            });
        });

        // Login: 5 per minute per email+IP
        RateLimiter::for('auth_login', function (Request $request) {
            $key = $request->ip() . '|' . $request->input('email');

            return Limit::perMinute(5)->by($key)->response(function () {
                return response()->json([
                    'message' => 'Too many login attempts. Please try again in a few minutes.',
                ], 429);
            });
        });

        // Register: 3 per hour per IP (prevent spam accounts)
        RateLimiter::for('auth_register', function (Request $request) {
            return Limit::perHour(3)->by($request->ip())->response(function () {
                return response()->json([
                    'message' => 'Too many registration attempts. Please try again later.',
                ], 429);
            });
        });

        // Forgot Password: 3 per hour per IP
        RateLimiter::for('auth_forgot', function (Request $request) {
            return Limit::perHour(3)->by($request->ip())->response(function () {
                return response()->json([
                    'message' => 'Too many password reset requests. Please try again later.',
                ], 429);
            });
        });

        // Reset Password: 5 per minute per email+IP
        RateLimiter::for('auth_reset', function (Request $request) {
            $key = $request->ip() . '|' . $request->input('email');

            return Limit::perMinute(5)->by($key)->response(function () {
                return response()->json([
                    'message' => 'Too many reset attempts. Please wait.',
                ], 429);
            });
        });
    }
}

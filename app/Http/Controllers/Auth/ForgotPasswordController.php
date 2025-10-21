<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ForgotPasswordController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private OtpService $otpService
    ) {}

    /**
     * Send password reset OTP to user's email.
     *
     * @param ForgotPasswordRequest $request
     * @return JsonResponse
     */
    public function sendOtp(ForgotPasswordRequest $request): JsonResponse
    {
        // Always return success message (security: don't reveal if email exists)
        $user = User::where('email', $request->email)->first();

        if ($user) {
            try {
                // Generate and send reset OTP
                $this->otpService->generate($user->email, 'reset');
            } catch (\Exception $e) {
                Log::error('Failed to send reset OTP', [
                    'email' => $request->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'message' => 'If your email is registered, you will receive a password reset code shortly.',
        ], 200);
    }
}

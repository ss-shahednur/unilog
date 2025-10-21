<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class VerifyController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private OtpService $otpService
    ) {}

    /**
     * Verify registration OTP and activate user account.
     *
     * @param VerifyOtpRequest $request
     * @return JsonResponse
     */
    public function verifyRegisterOtp(VerifyOtpRequest $request): JsonResponse
    {
        // Find user
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        // Check if already verified
        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email is already verified. You can log in now.',
            ], 200);
        }

        // Verify OTP
        $isValid = $this->otpService->verify(
            $request->email,
            'verify',
            $request->otp
        );

        if (!$isValid) {
            return response()->json([
                'message' => 'Invalid or expired OTP. Please request a new one.',
            ], 422);
        }

        // Mark email as verified
        DB::transaction(function () use ($user) {
            $user->update([
                'email_verified_at' => now(),
            ]);

            // Invalidate all remaining verify OTPs
            $this->otpService->invalidateAll($user->email, 'verify');
        });

        return response()->json([
            'message' => 'Email verified successfully. You can now log in.',
        ], 200);
    }
}

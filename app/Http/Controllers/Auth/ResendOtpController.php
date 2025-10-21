<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResendOtpRequest;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;

class ResendOtpController extends Controller
{
     /**
     * Create a new controller instance.
     */
    public function __construct(
        private OtpService $otpService
    ) {}

    /**
     * Resend verification OTP.
     *
     * @param ResendOtpRequest $request
     * @return JsonResponse
     */
    public function resend(ResendOtpRequest $request): JsonResponse
    {
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

        try {
            // Check remaining wait time
            $wait = $this->otpService->remainingWaitForResend($request->email, 'verify');
            if ($wait > 0) {
                return response()->json([
                    'message' => "Please wait {$wait} seconds before requesting another OTP.",
                    'retry_after' => $wait,
                ], 429);
            }

            // Generate and send new OTP
            $this->otpService->generate($request->email, 'verify');

            return response()->json([
                'message' => 'Verification code has been resent to your email.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 429);
        }
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;


class ResetPasswordController extends Controller
{
     /**
     * Create a new controller instance.
     */
    public function __construct(
        private OtpService $otpService
    ) {}

    /**
     * Reset user password using OTP.
     *
     * @param ResetPasswordRequest $request
     * @return JsonResponse
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        // Verify reset OTP
        $isValid = $this->otpService->verify(
            $request->email,
            'reset',
            $request->otp
        );

        if (!$isValid) {
            return response()->json([
                'message' => 'Invalid or expired OTP. Please request a new one.',
            ], 422);
        }

        // Update password and invalidate reset OTPs
        DB::transaction(function () use ($user, $request) {
            $user->update([
                'password' => $request->new_password, // Auto-hashed by model cast
            ]);

            // Invalidate all reset OTPs
            $this->otpService->invalidateAll($user->email, 'reset');

            // Optionally revoke all existing tokens (force re-login)
            $user->tokens()->delete();
        });

        return response()->json([
            'message' => 'Password has been reset successfully. Please log in with your new password.',
            'user' => new UserResource($user),
        ], 200);
    }
}

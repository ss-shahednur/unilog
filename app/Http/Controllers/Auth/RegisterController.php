<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(private OtpService $otpService) 
    {

    }

    /**
     * Register a new user and send verification OTP.
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Create user (email unverified)
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => $request->password, // Auto-hashed by model cast
                'referred_by' => $request->referred_by,
                'nid' => $request->nid,
                'email_verified_at' => null, // Not verified yet
            ]);

            // Generate and send verification OTP
            $this->otpService->generate($user->email, 'verify');

            DB::commit();

            return response()->json([
                'message' => 'Registration successful. Please check your email for the verification code.',
                'requires_verification' => true,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Registration failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Registration failed. Please try again.',
            ], 500);
        }
    }
}

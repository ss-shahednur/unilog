<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;


class ChangePasswordController extends Controller
{
    /**
     * Change authenticated user's password.
     *
     * @param ChangePasswordRequest $request
     * @return JsonResponse
     */
    public function change(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        // Verify old password
        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
                'errors' => [
                    'old_password' => ['The current password is incorrect.'],
                ],
            ], 422);
        }

        // Update password
        $user->update([
            'password' => $request->new_password, // Auto-hashed by model cast
        ]);

        // Optionally revoke all other tokens (keep current session)
        $currentToken = $user->currentAccessToken();
        $user->tokens()->where('id', '!=', $currentToken->id)->delete();

        return response()->json([
            'message' => 'Password changed successfully.',
            'user' => new UserResource($user),
        ], 200);
    }
}

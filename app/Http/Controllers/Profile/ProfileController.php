<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Update authenticated user's profile.
     *
     * @param UpdateProfileRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateProfileRequest $request, int $id): JsonResponse
    {
        $authUser = $request->user();

        // Authorization: User can only update their own profile
        if ($authUser->id !== (int) $id) {
            return response()->json([
                'message' => 'Unauthorized. You can only update your own profile.',
            ], 403);
        }

        $user = User::findOrFail($id);

        // Handle profile image upload
        $profileImagePath = $user->profile_image_path;

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($user->profile_image_path) {
                Storage::disk('public')->delete($user->profile_image_path);
            }

            // Store new image
            $image = $request->file('image');
            $filename = time() . '_' . $image->getClientOriginalName();
            $profileImagePath = $image->storeAs('avatars', $filename, 'public');
        }

        // Update user profile
        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'nid' => $request->nid,
            'profile_image_path' => $profileImagePath,
        ]);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => new UserResource($user->fresh()),
        ], 200);
    }
}

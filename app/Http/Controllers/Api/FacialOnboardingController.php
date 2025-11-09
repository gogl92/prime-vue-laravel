<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadReferenceImagesRequest;
use App\Models\User;
use App\Models\UserReferenceImage;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacialOnboardingController extends Controller
{
    /**
     * Display the user's reference images.
     * GET /api/users/{userId}/onboarding
     */
    public function index(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $images = $user->referenceImages;

        return response()->json([
            'images' => $images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'order' => $image->order,
                    'created_at' => $image->created_at,
                    'has_file' => file_exists($image->image_path),
                ];
            }),
            'count' => $images->count(),
        ]);
    }

    /**
     * Store reference images for facial recognition.
     * POST /api/users/{userId}/onboarding
     * @throws Exception
     */
    public function store(UploadReferenceImagesRequest $request, int|string $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $uploadedImages = [];

        try {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store("user-references/$user->id", 'local');
                $fullPath = storage_path("app/$path");

                $referenceImage = UserReferenceImage::create([
                    'user_id' => $user->id,
                    'image_path' => $fullPath,
                    'order' => $index + 1,
                ]);

                $uploadedImages[] = $referenceImage;
            }

            return response()->json([
                'message' => 'Reference images uploaded successfully',
                'images' => $uploadedImages,
            ], 201);
        } catch (Exception $e) {
            // Rollback: Delete uploaded images if something fails
            foreach ($uploadedImages as $uploadedImage) {
                if (file_exists($uploadedImage->image_path)) {
                    unlink($uploadedImage->image_path);
                }
                $uploadedImage->delete();
            }

            throw $e;
        }
    }

    /**
     * Delete all reference images for the user.
     * DELETE /api/users/{userId}/onboarding
     */
    public function destroy(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $images = $user->referenceImages;

        foreach ($images as $image) {
            if (file_exists($image->image_path)) {
                unlink($image->image_path);
            }
        }

        $user->referenceImages()->delete();

        return response()->json([
            'message' => 'All reference images deleted successfully',
        ]);
    }
}

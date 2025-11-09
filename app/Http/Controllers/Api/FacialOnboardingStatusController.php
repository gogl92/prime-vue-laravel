<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacialOnboardingStatusController extends Controller
{
    /**
     * Get user's facial recognition onboarding status.
     * GET /api/users/{userId}/onboarding/status
     */
    public function index(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $referenceImagesCount = $user->referenceImages()->count();

        return response()->json([
            'has_reference_images' => $referenceImagesCount === 5,
            'reference_images_count' => $referenceImagesCount,
            'onboarding_complete' => $referenceImagesCount === 5,
        ]);
    }
}

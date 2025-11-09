<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FacialReferenceImageController extends Controller
{
    /**
     * Serve a reference image file.
     * GET /api/users/{userId}/onboarding/images/{imageId}
     */
    public function show(Request $request, int $userId, int $imageId): BinaryFileResponse
    {
        $user = User::findOrFail($userId);
        $image = $user->referenceImages()->findOrFail($imageId);

        if (!file_exists($image->image_path)) {
            abort(404, 'Image file not found');
        }

        return response()->file($image->image_path);
    }
}

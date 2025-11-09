<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyFaceRequest;
use App\Models\User;
use App\Services\FaceMatchService;
use Exception;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class FacialVerificationController extends Controller
{
    public function __construct(
        protected FaceMatchService $faceMatchService
    ) {
    }

    /**
     * Verify a user's face against their reference images.
     * POST /api/users/{userId}/verify
     */
    public function store(VerifyFaceRequest $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        try {
            // Store uploaded image temporarily
            $uploadedImage = $request->file('image');
            $tempPath = $uploadedImage->store('temp-verification', 'local');
            $fullPath = storage_path("app/{$tempPath}");

            try {
                // Perform facial verification
                $isMatch = $this->faceMatchService->match($user, $fullPath);

                // Clean up temp file
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }

                if ($isMatch) {
                    return response()->json([
                        'verified' => true,
                        'message' => 'Face verified successfully',
                        'user_id' => $user->id,
                    ]);
                }

                return response()->json([
                    'verified' => false,
                    'message' => 'Face verification failed',
                    'error' => 'VERIFICATION_FAILED',
                ], 401);
            } catch (RuntimeException $e) {
                // Clean up temp file
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }

                return response()->json([
                    'verified' => false,
                    'message' => $e->getMessage(),
                    'error' => 'VERIFICATION_ERROR',
                ], 500);
            }
        } catch (Exception $e) {
            return response()->json([
                'verified' => false,
                'message' => 'An error occurred during verification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

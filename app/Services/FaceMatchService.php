<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\FacialRecognitionContract;
use App\Models\User;
use RuntimeException;

class FaceMatchService
{
    public function __construct(protected FacialRecognitionContract $facialRecognitionService)
    {
    }

    public function match(User $user, string $imageToCompare): bool
    {
        // Get a random reference image from the user's images
        $referenceImage = $user->referenceImages()->inRandomOrder()->first();

        if (!$referenceImage) {
            throw new RuntimeException('User does not have any reference images for facial recognition');
        }

        // Call the facial recognition API
        $response = $this->facialRecognitionService->verify(
            $referenceImage->image_path,
            $imageToCompare
        );

        // Validate the response
        if (!isset($response['face_match'])) {
            throw new RuntimeException('Invalid response from facial recognition service');
        }

        $faceMatch = $response['face_match'];
        $status = $faceMatch['status'] ?? null;
        $score = $faceMatch['score'] ?? 0;

        // Return true if status is "Approved" and score is greater than 90%
        return $status === 'Approved' && $score > 90;
    }
}

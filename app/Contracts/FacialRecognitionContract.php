<?php

declare(strict_types=1);

namespace App\Contracts;

interface FacialRecognitionContract
{
    /**
     * @param string $baseImagePath
     * @param string $faceImagePath
     * @return array
     */
    public function verify(string $baseImagePath, string $faceImagePath): array;
}

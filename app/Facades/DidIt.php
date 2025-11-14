<?php

declare(strict_types=1);

namespace App\Facades;

use App\Services\DidItService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array verify(string $baseImagePath, string $faceImagePath)
 * @method static array get(string $endpoint, array $query = [])
 * @method static array post(string $endpoint, array $data = [])
 * @method static array put(string $endpoint, array $data = [])
 * @method static array patch(string $endpoint, array $data = [])
 * @method static array delete(string $endpoint)
 *
 * @see DidItService
 */
class DidIt extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DidItService::class;
    }
}

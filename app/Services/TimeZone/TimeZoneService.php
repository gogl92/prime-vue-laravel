<?php

declare(strict_types=1);

namespace App\Services\TimeZone;

use App\Services\TimeZone\Drivers\GoogleTimeZoneDriver;
use App\Services\TimeZone\Drivers\TimeZoneDBDriver;
use App\Interfaces\TimeZoneDriverInterface;
use InvalidArgumentException;

class TimeZoneService
{
    protected TimeZoneDriverInterface $driver;

    public function __construct(string $driver = 'google')
    {
        $this->driver = $this->resolveDriver($driver);
    }

    protected function resolveDriver(string $driver): TimeZoneDriverInterface
    {
        return match ($driver) {
            'google' => new GoogleTimeZoneDriver(),
            'timezonedb' => new TimeZoneDBDriver(),
            default => throw new InvalidArgumentException("Invalid driver: $driver"),
        };
    }

    /**
     * @return array{lat: float, lng: float}
     */
    public function getCoordinates(string $street_1, string $street_2, string $city, string $state, string $country, string $zip): array
    {
        return $this->driver->getCoordinates($street_1, $street_2, $city, $state, $country, $zip);
    }

    public function getTimezone(float $latitude, float $longitude): string
    {
        return $this->driver->getTimezone($latitude, $longitude);
    }

    public function verifyAddress(string $street_1, string $street_2, string $city, string $state, string $country, string $zip): bool
    {
        return $this->driver->verifyAddress($street_1, $street_2, $city, $state, $country, $zip);
    }
}

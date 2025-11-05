<?php

declare(strict_types=1);

namespace App\Interfaces;

interface TimeZoneDriverInterface
{
    /**
     * Get coordinates from an address
     *
     * @return array{lat: float, lng: float}
     */
    public function getCoordinates(string $street_1, string $street_2, string $city, string $state, string $country, string $zip): array;

    /**
     * Get timezone from coordinates
     */
    public function getTimezone(float $latitude, float $longitude): string;

    /**
     * Verify if an address is valid
     */
    public function verifyAddress(string $street_1, string $street_2, string $city, string $state, string $country, string $zip): bool;
}

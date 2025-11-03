<?php

declare(strict_types=1);

namespace App\Services\TimeZone\Drivers;

use App\Exceptions\NotSupportedException;
use App\Interfaces\TimeZoneDriverInterface;
use Illuminate\Support\Facades\Http;

class TimeZoneDBDriver implements TimeZoneDriverInterface
{
    protected string $apiKey;
    protected string $apiUrl;

    /**
     * @throws NotSupportedException
     */
    public function __construct()
    {
        $this->apiKey = config('geolocalization.timezonedb.api_key');
        $this->apiUrl = config('geolocalization.timezonedb.api_url');

        if (empty($this->apiKey) || empty($this->apiUrl)) {
            throw new NotSupportedException('TimeZoneDB API key and URL must be provided.');
        }
    }

    /**
     * @throws NotSupportedException
     */
    public function getCoordinates(string $street_1, string $street_2, string $city, string $state, string $country, string $zip): array
    {
        throw new NotSupportedException('TimeZoneDB does not support address-to-coordinate conversion.');
    }

    public function getTimezone(float $latitude, float $longitude): string
    {
        $response = Http::get($this->apiUrl, [
            'key' => $this->apiKey,
            'format' => 'json',
            'by' => 'position',
            'lat' => $latitude,
            'lng' => $longitude,
        ]);

        $data = $response->json();

        if ($data['status'] !== 'OK') {
            return config('app.fall_back_time_zone');
        }

        return $data['zoneName'] ?? config('app.fall_back_time_zone');
    }

    /**
     * @throws NotSupportedException
     */
    public function verifyAddress(string $street_1, string $street_2, string $city, string $state, string $country, string $zip): bool
    {
        throw new NotSupportedException('TimeZoneDB does not support address verification.');
    }
}

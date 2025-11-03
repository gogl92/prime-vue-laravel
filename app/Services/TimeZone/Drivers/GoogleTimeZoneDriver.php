<?php

declare(strict_types=1);

namespace App\Services\TimeZone\Drivers;

use App\Exceptions\CoordinatesNotFoundException;
use Illuminate\Support\Facades\Http;
use App\Interfaces\TimeZoneDriverInterface;

/**
 * Class GoogleTimeZoneDriver
 * @package App\Services\TimeZone\Drivers
 */
class GoogleTimeZoneDriver implements TimeZoneDriverInterface {
    protected string $apiKey;

    protected const string MAPS_API_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

    public function __construct()
    {
        $this->apiKey = config('geolocalization.google.maps_api_key');
    }

    /**
     * @inheritDoc
     */
    public function getCoordinates(string $street_1, string $street_2, string $city, string $state, string $country, string $zip): array
    {
        $address = trim("$street_1 $street_2, $city, $state, $country, $zip");

        $geocodingUrl = self::MAPS_API_URL . urlencode($address) . '&key=' . $this->apiKey;
        $response = Http::get($geocodingUrl);
        $data = $response->json();

        if ($data['status'] !== 'OK') {
            throw new CoordinatesNotFoundException('Unable to get coordinates.');
        }

        return [
            $data['results'][0]['geometry']['location']['lat'],
            $data['results'][0]['geometry']['location']['lng'],
        ];
    }

    public function getTimezone(float $latitude, float $longitude): string
    {
        $timestamp = time();
        $timezoneUrl = "https://maps.googleapis.com/maps/api/timezone/json?location=$latitude,$longitude&timestamp=$timestamp&key=$this->apiKey";
        $response = Http::get($timezoneUrl);
        $data = $response->json();

        if ($data['status'] !== 'OK') {
            return config('app.fall_back_time_zone');
        }

        return $data['timeZoneId'] ?? config('app.fall_back_time_zone');
    }

    public function verifyAddress(string $street_1, string $street_2, string $city, string $state, string $country, string $zip): bool
    {
        $address = trim("$street_1 $street_2, $city, $state, $country, $zip");

        $geocodingUrl = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($address) . '&key=' . $this->apiKey;
        $response = Http::get($geocodingUrl);
        $data = $response->json();

        return $data['status'] === 'OK';
    }
}

<?php

declare(strict_types=1);

namespace Hschottm\ContaoEventsPlus\Geocoding;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleGeocoder
{
    public function __construct(private readonly HttpClientInterface $client)
    {
    }

    public function geocode(string $address, string $apiKey): ?string
    {
        if ('' === trim($address) || '' === trim($apiKey)) {
            return null;
        }

        try {
            $data = $this->client->request('GET', 'https://maps.googleapis.com/maps/api/geocode/json', [
                'query' => ['address' => $address, 'key' => $apiKey],
                'timeout' => 5,
                'max_duration' => 10,
            ])->toArray();
        } catch (ExceptionInterface $exception) {
            // Do not expose the request URL (which contains the API key).
            throw new GeocodingException('Google geocoding request failed.');
        }

        if ('ZERO_RESULTS' === ($data['status'] ?? null)) {
            return null;
        }
        if ('OK' !== ($data['status'] ?? null)) {
            throw new GeocodingException('Google geocoding returned an error.');
        }

        $location = $data['results'][0]['geometry']['location'] ?? [];
        if (!isset($location['lat'], $location['lng']) || !is_numeric($location['lat']) || !is_numeric($location['lng'])
            || abs((float) $location['lat']) > 90 || abs((float) $location['lng']) > 180) {
            throw new GeocodingException('Google geocoding returned invalid coordinates.');
        }

        return $location['lat'].','.$location['lng'];
    }
}

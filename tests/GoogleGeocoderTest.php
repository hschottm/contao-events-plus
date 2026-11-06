<?php

declare(strict_types=1);

namespace Hschottm\ContaoEventsPlus\Tests;

use Hschottm\ContaoEventsPlus\Geocoding\GeocodingException;
use Hschottm\ContaoEventsPlus\Geocoding\GoogleGeocoder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class GoogleGeocoderTest extends TestCase
{
    public function testSuccessfulRequest(): void
    {
        $client = new MockHttpClient(function ($method, $url, $options) {
            self::assertSame('GET', $method);
            self::assertStringStartsWith('https://maps.googleapis.com/maps/api/geocode/json?', $url);
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            self::assertSame(['address' => 'Teststraße 1 01067 Dresden', 'key' => 'test-key'], $query);
            self::assertSame(10.0, (float) $options['max_duration']);
            return new MockResponse('{"status":"OK","results":[{"geometry":{"location":{"lat":51.05,"lng":13.74}}}]}');
        });
        self::assertSame('51.05,13.74', (new GoogleGeocoder($client))->geocode('Teststraße 1 01067 Dresden', 'test-key'));
    }

    public function testNoRequestWithoutAddressOrKey(): void
    {
        $geocoder = new GoogleGeocoder(new MockHttpClient(function () { self::fail('Unexpected request'); }));
        self::assertNull($geocoder->geocode(' ', 'key'));
        self::assertNull($geocoder->geocode('Dresden', ' '));
    }

    public function testZeroResults(): void
    {
        $geocoder = new GoogleGeocoder(new MockHttpClient(new MockResponse('{"status":"ZERO_RESULTS","results":[]}')));
        self::assertNull($geocoder->geocode('Unknown address', 'key'));
    }

    #[DataProvider('invalidResponses')]
    public function testInvalidResponseIsReportedWithoutExposingKey(string $body, int $httpCode): void
    {
        $geocoder = new GoogleGeocoder(new MockHttpClient(new MockResponse($body, ['http_code' => $httpCode])));
        try {
            $geocoder->geocode('Dresden', 'secret-key');
            self::fail('Expected geocoding error');
        } catch (GeocodingException $exception) {
            self::assertStringNotContainsString('secret-key', $exception->getMessage());
            self::assertNull($exception->getPrevious());
        }
    }

    public static function invalidResponses(): array
    {
        return [
            ['{"status":"REQUEST_DENIED","error_message":"secret-key is invalid"}', 200],
            ['{"status":"OVER_QUERY_LIMIT"}', 200],
            ['{"status":"OK","results":[]}', 200],
            ['{"status":"OK","results":[{"geometry":{"location":{"lat":100,"lng":13}}}]}', 200],
            ['not json', 200],
            ['unavailable', 503],
        ];
    }
}

<?php

declare(strict_types=1);

namespace Hschottm\ContaoEventsPlus\EventListener;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Message;
use Doctrine\DBAL\Connection;
use Hschottm\ContaoEventsPlus\Geocoding\GeocodingException;
use Hschottm\ContaoEventsPlus\Geocoding\GoogleGeocoder;

class CalendarEventsListener
{
    public function __construct(private readonly Connection $connection, private readonly GoogleGeocoder $geocoder)
    {
    }

    #[AsCallback(table: 'tl_calendar_events', target: 'config.onsubmit')]
    public function onSubmit(?DataContainer $dc = null): void
    {
        if (!$dc?->id) {
            return;
        }

        $this->connection->executeStatement('UPDATE tl_calendar_events SET dateAdded=? WHERE id=? AND dateAdded=0', [time(), $dc->id]);

        $apiKey = trim((string) Config::get('utilsGoogleApiKey'));
        if (!Config::get('eventsPlusEnableAutomaticCoordinates') || '' === $apiKey) {
            return;
        }

        // Read after saving so a newly entered or edited address is used immediately.
        $event = $this->connection->fetchAssociative('SELECT street, postal, city, coordinates FROM tl_calendar_events WHERE id=?', [$dc->id]);
        if (!$event || '' !== trim((string) $event['coordinates'])) {
            return;
        }

        $address = trim(implode(' ', array_filter([
            trim((string) $event['street']), trim((string) $event['postal']), trim((string) $event['city']),
        ], static fn (string $part): bool => '' !== $part)));
        if ('' === $address) {
            return;
        }

        try {
            $coordinates = $this->geocoder->geocode($address, $apiKey);
        } catch (GeocodingException) {
            Message::addError($GLOBALS['TL_LANG']['ERR']['eventsPlusGeocodingFailed']);
            return;
        }

        if (null === $coordinates) {
            Message::addInfo($GLOBALS['TL_LANG']['MSC']['eventsPlusNoCoordinates']);
            return;
        }

        $this->connection->update('tl_calendar_events', ['coordinates' => $coordinates], ['id' => $dc->id]);
    }

    #[AsCallback(table: 'tl_calendar_events', target: 'config.oncopy')]
    public function onCopy(int $insertId): void
    {
        $this->connection->update('tl_calendar_events', ['dateAdded' => time()], ['id' => $insertId]);
    }
}

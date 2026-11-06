<?php

declare(strict_types=1);

namespace Hschottm\ContaoEventsPlus\Tests;

use Contao\Config;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Hschottm\ContaoEventsPlus\EventListener\CalendarEventsListener;
use Hschottm\ContaoEventsPlus\Geocoding\GoogleGeocoder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CalendarEventsListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Config::set('utilsGoogleApiKey', '');
        Config::set('eventsPlusEnableAutomaticCoordinates', false);
    }

    private function dataContainer(): DataContainer
    {
        $dc = $this->getMockBuilder(DataContainer::class)->disableOriginalConstructor()->onlyMethods(['getPalette', 'save'])->getMock();
        $dc->id = 42;
        return $dc;
    }

    #[DataProvider('disabledSettings')]
    public function testNoRetrievalWithoutBothPrerequisites(bool $enabled, string $key): void
    {
        Config::set('eventsPlusEnableAutomaticCoordinates', $enabled);
        Config::set('utilsGoogleApiKey', $key);
        $db = $this->createMock(Connection::class);
        $db->expects(self::once())->method('executeStatement');
        $db->expects(self::never())->method('fetchAssociative');
        $geocoder = $this->createMock(GoogleGeocoder::class);
        $geocoder->expects(self::never())->method('geocode');
        (new CalendarEventsListener($db, $geocoder))->onSubmit($this->dataContainer());
    }

    public static function disabledSettings(): array
    {
        return [[false, ''], [false, 'key'], [true, '']];
    }

    public function testUsesSavedAddressAndWritesCoordinates(): void
    {
        Config::set('eventsPlusEnableAutomaticCoordinates', true);
        Config::set('utilsGoogleApiKey', 'key');
        $db = $this->createMock(Connection::class);
        $db->expects(self::once())->method('executeStatement')->with('UPDATE tl_calendar_events SET dateAdded=? WHERE id=? AND dateAdded=0', self::callback(fn ($args) => $args[0] > 0 && $args[1] == 42));
        $db->expects(self::once())->method('fetchAssociative')->with('SELECT street, postal, city, coordinates FROM tl_calendar_events WHERE id=?', [42])->willReturn(['street' => 'New street 1', 'postal' => '01067', 'city' => 'Dresden', 'coordinates' => '']);
        $geocoder = $this->createMock(GoogleGeocoder::class);
        $geocoder->expects(self::once())->method('geocode')->with('New street 1 01067 Dresden', 'key')->willReturn('51.05,13.74');
        $db->expects(self::once())->method('update')->with('tl_calendar_events', ['coordinates' => '51.05,13.74'], ['id' => 42]);
        (new CalendarEventsListener($db, $geocoder))->onSubmit($this->dataContainer());
    }

    #[DataProvider('nonGeocodableEvents')]
    public function testPreservesCoordinatesAndSkipsMissingAddresses(array|false $event): void
    {
        Config::set('eventsPlusEnableAutomaticCoordinates', true);
        Config::set('utilsGoogleApiKey', 'key');
        $db = $this->createMock(Connection::class);
        $db->method('fetchAssociative')->willReturn($event);
        $db->expects(self::never())->method('update');
        $geocoder = $this->createMock(GoogleGeocoder::class);
        $geocoder->expects(self::never())->method('geocode');
        (new CalendarEventsListener($db, $geocoder))->onSubmit($this->dataContainer());
    }

    public static function nonGeocodableEvents(): array
    {
        return [
            [['street' => 'Test 1', 'postal' => '01067', 'city' => 'Dresden', 'coordinates' => '51,13']],
            [['street' => '', 'postal' => '', 'city' => '', 'coordinates' => '']],
            [false],
        ];
    }

    public function testCopyGetsNewCreationTimestamp(): void
    {
        $db = $this->createMock(Connection::class);
        $db->expects(self::once())->method('update')->with('tl_calendar_events', self::callback(fn ($data) => abs(time() - $data['dateAdded']) < 2), ['id' => 43]);
        (new CalendarEventsListener($db, $this->createMock(GoogleGeocoder::class)))->onCopy(43);
    }

    public function testMissingDataContainerDoesNotAccessDatabase(): void
    {
        $db = $this->createMock(Connection::class);
        $db->expects(self::never())->method('executeStatement');
        (new CalendarEventsListener($db, $this->createMock(GoogleGeocoder::class)))->onSubmit();
    }
}

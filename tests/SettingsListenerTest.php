<?php

declare(strict_types=1);

namespace Hschottm\ContaoEventsPlus\Tests;

use Contao\Config;
use Hschottm\ContaoEventsPlus\EventListener\SettingsListener;
use PHPUnit\Framework\TestCase;

class SettingsListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Config::set('utilsGoogleApiKey', '');
        Config::set('eventsPlusEnableAutomaticCoordinates', false);
        unset($GLOBALS['TL_DCA']);
    }

    public function testCheckboxIsDisabledAndCannotBeEnabledWithoutKey(): void
    {
        Config::set('utilsGoogleApiKey', ' ');
        $listener = new SettingsListener();
        $listener->onLoad();
        self::assertTrue($GLOBALS['TL_DCA']['tl_settings']['fields']['eventsPlusEnableAutomaticCoordinates']['eval']['disabled']);
        self::assertFalse($listener->saveEnabled('1'));
    }

    public function testKeyAllowsExplicitOptInButDoesNotEnableAutomatically(): void
    {
        Config::set('utilsGoogleApiKey', 'key');
        $listener = new SettingsListener();
        $listener->onLoad();
        self::assertFalse($GLOBALS['TL_DCA']['tl_settings']['fields']['eventsPlusEnableAutomaticCoordinates']['eval']['disabled']);
        self::assertTrue($listener->saveEnabled('1'));
        self::assertFalse($listener->saveEnabled(''));
    }

    public function testRemovingKeyPreventsSavingEnabledState(): void
    {
        Config::set('eventsPlusEnableAutomaticCoordinates', true);
        Config::set('utilsGoogleApiKey', '');
        self::assertFalse((new SettingsListener())->saveEnabled(true));
    }
}

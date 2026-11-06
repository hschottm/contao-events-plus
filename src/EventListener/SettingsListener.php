<?php

declare(strict_types=1);

namespace Hschottm\ContaoEventsPlus\EventListener;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;

class SettingsListener
{
    #[AsCallback(table: 'tl_settings', target: 'config.onload')]
    public function onLoad(): void
    {
        $GLOBALS['TL_DCA']['tl_settings']['fields']['eventsPlusEnableAutomaticCoordinates']['eval']['disabled'] =
            '' === trim((string) Config::get('utilsGoogleApiKey'));
    }

    #[AsCallback(table: 'tl_settings', target: 'fields.eventsPlusEnableAutomaticCoordinates.save')]
    public function saveEnabled(mixed $value): bool
    {
        // The key is saved before this checkbox. Also enforce the prerequisite
        // server-side, including crafted requests and a key removed in this form.
        return (bool) $value && '' !== trim((string) Config::get('utilsGoogleApiKey'));
    }

    #[AsCallback(table: 'tl_settings', target: 'config.onsubmit')]
    public function onSubmit(): void
    {
        if ('' === trim((string) Config::get('utilsGoogleApiKey')) && Config::get('eventsPlusEnableAutomaticCoordinates')) {
            Config::persist('eventsPlusEnableAutomaticCoordinates', false);
            Config::set('eventsPlusEnableAutomaticCoordinates', false);
        }
    }
}

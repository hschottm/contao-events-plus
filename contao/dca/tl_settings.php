<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$dca = &$GLOBALS['TL_DCA']['tl_settings'];

// Keep the existing key name for projects migrating from Events/Utils Bundle.
$dca['fields']['utilsGoogleApiKey'] ??= [
    'label' => &$GLOBALS['TL_LANG']['tl_settings']['utilsGoogleApiKey'],
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'hideInput' => true, 'maxlength' => 255],
];
$dca['fields']['eventsPlusEnableAutomaticCoordinates'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_settings']['eventsPlusEnableAutomaticCoordinates'],
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
];

PaletteManipulator::create()
    ->removeField('utilsGoogleApiKey')
    ->addLegend('eventsPlusGoogleMaps_legend', '', PaletteManipulator::POSITION_APPEND)
    ->addField(['utilsGoogleApiKey', 'eventsPlusEnableAutomaticCoordinates'], 'eventsPlusGoogleMaps_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_settings');

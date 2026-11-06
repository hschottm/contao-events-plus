<?php

/*
 * Derived from heimrichhannot/contao-events-bundle.
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 * Copyright (c) 2026 Helmut Schottmüller
 * SPDX-License-Identifier: LGPL-3.0-or-later
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$dca = &$GLOBALS['TL_DCA']['tl_calendar_events'];

$fields = [
    'dateAdded' => [
        'label' => &$GLOBALS['TL_LANG']['MSC']['dateAdded'],
        'sorting' => true,
        'flag' => 6,
        'eval' => ['rgxp' => 'datim', 'doNotCopy' => true, 'noSubmissionField' => true],
        'sql' => "int(10) unsigned NOT NULL default '0'",
    ],
    'subTitle' => [
        'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['subTitle'],
        'exclude' => true,
        'search' => true,
        'inputType' => 'text',
        'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
        'sql' => "varchar(255) NOT NULL default ''",
    ],
    'shortTitle' => [
        'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['shortTitle'],
        'inputType' => 'text',
        'exclude' => true,
        'eval' => ['tl_class' => 'w50'],
        'sql' => "varchar(255) NOT NULL default ''",
    ],
    'locationAdditional' => [
        'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['locationAdditional'],
        'inputType' => 'text',
        'exclude' => true,
        'eval' => ['tl_class' => 'w50'],
        'sql' => "varchar(255) NOT NULL default ''",
    ],
    'street' => [
        'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['street'],
        'inputType' => 'text',
        'exclude' => true,
        'eval' => ['tl_class' => 'w50'],
        'sql' => "varchar(128) NOT NULL default ''",
    ],
    'postal' => [
        'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['postal'],
        'inputType' => 'text',
        'exclude' => true,
        'eval' => ['tl_class' => 'w50'],
        'sql' => "varchar(64) NOT NULL default ''",
    ],
    'city' => [
        'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['city'],
        'inputType' => 'text',
        'exclude' => true,
        'eval' => ['tl_class' => 'w50'],
        'sql' => "varchar(128) NOT NULL default ''",
    ],
    'coordinates' => [
        'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['coordinates'],
        'inputType' => 'text',
        'exclude' => true,
        'eval' => ['tl_class' => 'w50'],
        'sql' => "varchar(64) NOT NULL default ''",
    ],
    'website' => [
        'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['website'],
        'inputType' => 'text',
        'exclude' => true,
        'eval' => ['tl_class' => 'w50', 'rgxp' => 'url', 'maxlength' => 255],
        'sql' => "varchar(255) NOT NULL default ''",
    ],
];

$dca['fields'] = array_merge(is_array($dca['fields']) ? $dca['fields'] : [], $fields);


// Extend every source palette, while keeping Contao's location/address fields.
foreach ($dca['palettes'] as $name => $palette) {
    if (!is_string($palette) || !str_contains($palette, 'title')) {
        continue;
    }

    PaletteManipulator::create()
        ->addField('shortTitle', 'alias', PaletteManipulator::POSITION_AFTER)
        ->addField('subTitle', 'shortTitle', PaletteManipulator::POSITION_AFTER)
        ->addLegend('eventsPlusLocation_legend', 'details_legend', PaletteManipulator::POSITION_AFTER)
        ->addField(['street', 'postal', 'city', 'coordinates', 'locationAdditional'], 'eventsPlusLocation_legend', PaletteManipulator::POSITION_APPEND)
        ->addLegend('eventsPlusContact_legend', 'eventsPlusLocation_legend', PaletteManipulator::POSITION_AFTER)
        ->addField('website', 'eventsPlusContact_legend', PaletteManipulator::POSITION_APPEND)
        ->applyToPalette($name, 'tl_calendar_events');
}

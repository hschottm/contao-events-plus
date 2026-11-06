<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use Contao\Config;
use Contao\Controller;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\ManagerPlugin\PluginLoader;
use Contao\System;
use Hschottm\ContaoEventsPlus\ContaoManager\Plugin;
use Symfony\Component\Filesystem\Filesystem;

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$project = sys_get_temp_dir().'/contao-events-plus-'.bin2hex(random_bytes(6));
mkdir($project.'/config', 0777, true);
mkdir($project.'/system/tmp', 0777, true);
mkdir($project.'/system/config', 0777, true);
file_put_contents($project.'/composer.json', '{}');
file_put_contents($project.'/config/config.yaml', "framework:\n  secret: smoke-test\ndoctrine:\n  dbal:\n    url: mysql://test:test@localhost/test?serverVersion=10.11.0-MariaDB\n");
register_shutdown_function(static fn () => (new Filesystem())->remove($project));
ContaoKernel::setProjectDir($project);
$kernel = new ContaoKernel('test', true);
$plugins = [];
$installed = json_decode(file_get_contents(dirname(__DIR__).'/vendor/composer/installed.json'), true, flags: JSON_THROW_ON_ERROR);
foreach ($installed['packages'] as $package) {
    if (isset($package['extra']['contao-manager-plugin'])) {
        $class = $package['extra']['contao-manager-plugin'];
        $plugins[$package['name']] = new $class();
    }
}
$plugins['hschottm/contao-events-plus'] = new Plugin();
$kernel->setPluginLoader(new PluginLoader(null, $plugins));
$kernel->boot();
$kernel->getContainer()->get('contao.framework')->initialize();
echo "Contao kernel and framework initialized.\n";

Controller::loadDataContainer('tl_calendar_events');
Controller::loadDataContainer('tl_settings');
$fields = ['dateAdded', 'subTitle', 'shortTitle', 'street', 'postal', 'city', 'coordinates', 'locationAdditional', 'website'];
foreach ($fields as $field) {
    check(isset($GLOBALS['TL_DCA']['tl_calendar_events']['fields'][$field]), 'Missing field '.$field);
}
foreach (['default', 'internal', 'article', 'external'] as $palette) {
    foreach (array_diff($fields, ['dateAdded']) as $field) {
        check(str_contains($GLOBALS['TL_DCA']['tl_calendar_events']['palettes'][$palette], ','.$field), "Missing $field in $palette");
    }
}
check(!isset($GLOBALS['TL_DCA']['tl_calendar_events']['fields']['parentEvent']), 'Unexpected sub-event relation');
check(!isset($GLOBALS['BE_MOD']['content']['calendar_subevents']), 'Unexpected sub-event module');
check(!Config::get('eventsPlusEnableAutomaticCoordinates'), 'Geocoding must be opt-in');

// Resolve registered attribute callbacks through the actual compiled container.
foreach (['tl_calendar_events' => ['onsubmit_callback', 'oncopy_callback'], 'tl_settings' => ['onload_callback', 'onsubmit_callback']] as $table => $callbacks) {
    foreach ($callbacks as $name) {
        $found = false;
        foreach ($GLOBALS['TL_DCA'][$table]['config'][$name] ?? [] as $callback) {
            if (is_array($callback) && str_contains($callback[0], 'Hschottm')) {
                check(is_callable([System::importStatic($callback[0]), $callback[1]]), "Invalid callback $table/$name");
                $found = true;
            }
        }
        check($found, "Missing callback $table/$name");
    }
}
$settings = System::importStatic(Hschottm\ContaoEventsPlus\EventListener\SettingsListener::class);
Config::set('utilsGoogleApiKey', '');
$settings->onLoad();
check($GLOBALS['TL_DCA']['tl_settings']['fields']['eventsPlusEnableAutomaticCoordinates']['eval']['disabled'], 'Checkbox must be disabled without a key');
Config::set('utilsGoogleApiKey', 'test-key');
$settings->onLoad();
check(!$GLOBALS['TL_DCA']['tl_settings']['fields']['eventsPlusEnableAutomaticCoordinates']['eval']['disabled'], 'Checkbox must be available with a key');
Config::set('eventsPlusEnableAutomaticCoordinates', true);
Config::set('utilsGoogleApiKey', '');
$settings->onSubmit();
Config::getInstance()->save();
check(false === Config::get('eventsPlusEnableAutomaticCoordinates'), 'Removing the key must persistently disable geocoding');
check(is_file($project.'/system/config/localconfig.php'), 'Missing persisted settings');


foreach (['en', 'de'] as $language) {
    System::loadLanguageFile('default', $language, true);
    System::loadLanguageFile('tl_calendar_events', $language, true);
    System::loadLanguageFile('tl_settings', $language, true);
    foreach (array_diff($fields, ['dateAdded']) as $field) {
        check(!empty($GLOBALS['TL_LANG']['tl_calendar_events'][$field][0]), "Missing $language label for $field");
        check(!empty($GLOBALS['TL_LANG']['tl_calendar_events'][$field][1]), "Missing $language help for $field");
    }
    foreach (['utilsGoogleApiKey', 'eventsPlusEnableAutomaticCoordinates'] as $field) {
        check(!empty($GLOBALS['TL_LANG']['tl_settings'][$field][0]), "Missing $language settings label for $field");
    }
}
echo "All fields, four source palettes, callbacks, opt-in settings and EN/DE translations verified.\n";
$kernel->shutdown();

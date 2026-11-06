<?php

declare(strict_types=1);

namespace Hschottm\ContaoEventsPlus\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TranslationsTest extends TestCase
{
    public function testAllBundledContaoBackendLanguagesAreCovered(): void
    {
        $core = dirname(__DIR__).'/vendor/contao/core-bundle/contao/languages';
        foreach (glob($core.'/*', GLOB_ONLYDIR) as $directory) {
            self::assertDirectoryExists(dirname(__DIR__).'/contao/languages/'.basename($directory));
        }
    }

    #[DataProvider('locales')]
    public function testEveryLanguageProvidesEveryTextWithoutFallback(string $locale): void
    {
        $english = self::readTranslations('en');
        $translated = self::readTranslations($locale);
        self::assertSame(array_keys($english), array_keys($translated), 'Translation keys differ for '.$locale);
        foreach ($translated as $key => $text) {
            self::assertIsString($text, $locale.': '.$key);
            self::assertNotSame('', trim($text), $locale.': '.$key);
            self::assertSame(1, preg_match('//u', $text), 'Invalid UTF-8: '.$locale.': '.$key);
        }
    }

    public static function locales(): iterable
    {
        foreach (glob(dirname(__DIR__).'/contao/languages/*', GLOB_ONLYDIR) as $directory) {
            yield basename($directory) => [basename($directory)];
        }
    }

    private static function readTranslations(string $locale): array
    {
        $original = $GLOBALS['TL_LANG'] ?? [];
        $GLOBALS['TL_LANG'] = [];
        try {
            foreach (['default', 'tl_calendar_events', 'tl_settings'] as $file) {
                require dirname(__DIR__).'/contao/languages/'.$locale.'/'.$file.'.php';
            }
            $result = [];
            $flatten = static function (array $values, string $prefix = '') use (&$flatten, &$result): void {
                foreach ($values as $key => $value) {
                    if (is_array($value)) {
                        $flatten($value, $prefix.$key.'.');
                    } else {
                        $result[$prefix.$key] = $value;
                    }
                }
            };
            $flatten($GLOBALS['TL_LANG']);
            ksort($result);
            return $result;
        } finally {
            $GLOBALS['TL_LANG'] = $original;
        }
    }
}

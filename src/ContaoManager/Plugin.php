<?php

declare(strict_types=1);

namespace Hschottm\ContaoEventsPlus\ContaoManager;

use Contao\CalendarBundle\ContaoCalendarBundle;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Hschottm\ContaoEventsPlus\ContaoEventsPlusBundle;
use Symfony\Component\Config\Loader\LoaderInterface;

class Plugin implements BundlePluginInterface, ConfigPluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [BundleConfig::create(ContaoEventsPlusBundle::class)->setLoadAfter([
            ContaoCoreBundle::class,
            ContaoCalendarBundle::class,
        ])];
    }

    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig): void
    {
        $loader->load(__DIR__.'/../../config/services.yaml');
    }
}

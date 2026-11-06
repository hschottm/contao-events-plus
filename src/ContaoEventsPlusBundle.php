<?php

declare(strict_types=1);

namespace Hschottm\ContaoEventsPlus;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoEventsPlusBundle extends Bundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}

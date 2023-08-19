<?php

declare(strict_types=1);

namespace ItalyStrap\Tests\Modules;

use Auryn\Test\{SharedAliasedInterface, SharedClass};
use ItalyStrap\Empress\AurynConfig;

class ModuleStub1
{
    public function __invoke(): array
    {
        return  [
            AurynConfig::ALIASES => [
                SharedAliasedInterface::class => SharedClass::class,
            ],
            AurynConfig::SHARING => [
            ],
            AurynConfig::DEFINITIONS => [
            ],
        ];
    }
}

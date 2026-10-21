<?php

declare(strict_types=1);

namespace ItalyStrap\Empress\Tests;

use Auryn\Injector;
use ItalyStrap\Empress\AurynConfigInterface;
use ItalyStrap\Empress\Extension;

final class ContainerBuilderExtensionStub implements Extension
{
    public function name(): string
    {
        return self::class;
    }

    public function execute(AurynConfigInterface $application): void
    {
        $application->walk(
            'container_builder_test_aliases',
            static function (string $alias, string $typeHint, Injector $injector): void {
                $injector->alias($typeHint, $alias);
            }
        );
    }
}

<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

use Auryn\Injector;
use Psr\Container\ContainerInterface;

final class Container implements ContainerInterface
{
    private Injector $injector;

    public function __construct(
        Injector $injector
    ) {
        $this->injector = $injector;
    }

    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException(\sprintf("Service '%s' not found", $id));
        }

        try {
            return $this->injector->make($id);
        } catch (\Throwable $throwable) {
            throw new ContainerException(
                \sprintf("Error while retrieving service '%s'", $id),
                0,
                $throwable
            );
        }
    }

    public function has(string $id): bool
    {
        if (\class_exists($id)) {
            return true;
        }

        return $this->injectorHas($id);
    }

    private function injectorHas(string $id): bool
    {
        $details = $this->injector->inspect($id, 31);
        return (bool) \array_filter($details);
    }
}

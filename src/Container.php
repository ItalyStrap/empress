<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

use Auryn\Injector;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

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
            throw new class ("Service '$id' not found") extends \Exception implements NotFoundExceptionInterface {
            };
        }

        return $this->injector->make($id);
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

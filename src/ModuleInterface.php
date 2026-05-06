<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

interface ModuleInterface
{
    public function __invoke(): iterable;
}

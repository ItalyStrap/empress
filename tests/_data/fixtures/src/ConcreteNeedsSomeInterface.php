<?php

declare(strict_types=1);

namespace ItalyStrap\Tests;

class ConcreteNeedsSomeInterface
{
    /**
     * @var SomeInterface
     */
    private SomeInterface $someInterface;

    public function __construct(SomeInterface $someInterface)
    {
        $this->someInterface = $someInterface;
    }

    public function someInterface(): SomeInterface
    {
        return $this->someInterface;
    }
}

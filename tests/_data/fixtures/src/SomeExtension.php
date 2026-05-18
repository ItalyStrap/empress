<?php

declare(strict_types=1);

namespace ItalyStrap\Empress\Tests;

use ItalyStrap\Empress\AurynConfigInterface;
use ItalyStrap\Empress\Extension;

class SomeExtension implements Extension
{
    public function name(): string
    {
        return __CLASS__;
    }

    public function execute(AurynConfigInterface $application): void
    {
        echo $this->name();
    }
}

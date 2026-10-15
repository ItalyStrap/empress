<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

interface Extension
{
    public function name(): string;

    /**
     * @return void
     */
    public function execute(AurynConfigInterface $application);
}

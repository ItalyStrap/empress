<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

use ItalyStrap\Finder\FinderInterface;

final readonly class PhpFileProvider
{
    /**
     * @param string $pattern A glob pattern by which to look up config files.
     */
    public function __construct(private string $pattern, private FinderInterface $finder)
    {
    }

    public function __invoke(): \Generator
    {
        $this->finder->names([$this->pattern]);
        /**
         * @var \SplFileInfo $file
         */
        foreach ($this->finder as $file) {
            yield include $file;
        }
    }
}

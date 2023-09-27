<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

use ItalyStrap\Finder\FinderInterface;

/**
 * @psalm-api
 */
final class PhpFileProvider
{
    private string $pattern;

    private FinderInterface $finder;

    /**
     * @param string $pattern A glob pattern by which to look up config files.
     */
    public function __construct(string $pattern, FinderInterface $finder)
    {
        $this->pattern = $pattern;
        $this->finder = $finder;
    }

    /**
     * @return \Generator
     */
    public function __invoke(): \Generator
    {
        $this->finder->names([$this->pattern]);
        /**
         * @var \SplFileInfo $file
         */
        foreach ($this->finder as $file) {
            /** @psalm-suppress UnresolvableInclude */
            yield include $file;
        }
    }
}

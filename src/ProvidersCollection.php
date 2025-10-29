<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

use Auryn\Injector;
use Auryn\InjectionException;
use ItalyStrap\Config\ConfigInterface;

/**
 * @psalm-api
 */
class ProvidersCollection
{
    private ConfigInterface $config;
    private Injector $injector;
    private ProvidersCache $cache;
    private iterable $providers;

    public function __construct(
        Injector $injector,
        ConfigInterface $config,
        ProvidersCache $cache = null,
        iterable $providers = []
    ) {
        $this->injector = $injector;
        $this->config = $config;
        $this->cache = $cache ??= new ProvidersCache();
        $this->providers = $providers;
    }

    public function build(): void
    {
        if ($this->cache->read($this->config)) {
            return;
        }

        $result = [];
        /** @var array<string, int|string|array> $subArray */
        foreach ($this->loadCollectionFromProviders() as $subArray) {
            $this->processCollections($subArray, $result);
        }

        $this->config->merge($result);

        if ((bool)$this->config->get(ProvidersCacheInterface::ENABLE_CACHE, false)) {
            $this->cache->write($this->config);
        }
    }

    private function processCollections(array $subArray, array &$result): void
    {
        foreach ($subArray as $key => $value) {
            if (!array_key_exists($key, $result)) {
                $result[$key] = [];
            }

            if (!is_array($value)) {
                /** @psalm-suppress MixedAssignment */
                $result[$key] = $value;
                continue;
            }

            $result[$key] = \array_merge((array)$result[$key], $value);
        }
    }

    private function loadCollectionFromProviders(): \Generator
    {
        /** @var object|array|class-string $provider */
        foreach ($this->providers as $provider) {
            try {
                $result = $this->injector->execute($provider);
            } catch (InjectionException | \Throwable $e) {
                throw new \ErrorException(
                    \sprintf(
                        'An error occurred when executing %s: %s',
                        is_object($provider) ? get_class($provider) : gettype($provider),
                        $e->getMessage()
                    ),
                    0,
                    1,
                    __FILE__,
                    __LINE__,
                    $e
                );
            }

            if ($result instanceof \Generator) {
                yield from $result;
                continue;
            }

            if (!\is_array($result)) {
                throw new \RuntimeException(
                    \sprintf(
                        'The provider %s must return an array or a Generator, %s given',
                        is_object($provider) ? get_class($provider) : gettype($provider),
                        \gettype($result)
                    )
                );
            }

            yield $result;
        }
    }

    public function collection(): ConfigInterface
    {
        return $this->config;
    }
}

<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

use Auryn\Injector;
use ItalyStrap\Config\ConfigInterface;
use ItalyStrap\Config\NodeManipulationInterface;

/**
 * @phpstan-type Provider callable|class-string|array{0: class-string|object, 1: non-empty-string}|object
 * @phpstan-type Configuration array<array-key, mixed>
 */
final class ProvidersCollection
{
    private Injector $injector;
    private ConfigInterface $config;
    private ProvidersCacheInterface $cache;
    /**
     * @var iterable<Provider>
     */
    private iterable $providers;

    /**
     * @param ConfigInterface&NodeManipulationInterface $config
     * @param iterable<Provider> $providers
     */
    public function __construct(
        Injector $injector,
        ConfigInterface $config,
        ?ProvidersCacheInterface $cache = null,
        iterable $providers = []
    ) {
        if (!$config instanceof NodeManipulationInterface) {
            throw new \InvalidArgumentException(\sprintf(
                '$config must implement %s',
                NodeManipulationInterface::class
            ));
        }

        $this->injector = $injector;
        $this->config = $config;
        $this->cache = $cache ?: new ProvidersCache();
        $this->providers = $providers;
    }

    public function aggregate(): void
    {
        if ($this->cache->read($this->config)) {
            return;
        }

        $result = [];
        $appendSections = [];
        foreach ($this->loadConfigurationsFromProviders() as $configuration) {
            $this->mergeConfiguration($configuration, $result, $appendSections);
        }

        $this->config->merge($result);
        foreach ($appendSections as $key => $value) {
            $this->config->appendTo($key, $value);
        }

        if ((bool)$this->config->get(ProvidersCacheInterface::ENABLE_CACHE, false)) {
            $this->cache->write($this->config);
        }
    }

    /**
     * @param Configuration $configuration
     * @param array $result
     * @param array $appendSections
     */
    private function mergeConfiguration(
        array $configuration,
        array &$result,
        array &$appendSections
    ): void {
        foreach ($configuration as $key => $value) {
            if ($this->isAppendSection((string)$key)) {
                $appendSections[$key] = $this->uniqueValues(\array_merge(
                    (array)($appendSections[$key] ?? []),
                    (array)$value
                ));
                continue;
            }

            if (!is_array($value)) {
                $result[$key] = $value;
                continue;
            }

            $current = [];

            if (array_key_exists($key, $result) && is_array($result[$key])) {
                $current = $result[$key];
            }

            $result[$key] = \array_replace_recursive($current, $value);
        }
    }

    private function isAppendSection(string $key): bool
    {
        return \in_array($key, [
            AurynConfig::PROXY,
            AurynConfig::SHARING,
        ], true);
    }

    /**
     * @param array<array-key, mixed> $values
     * @return array<int, mixed>
     */
    private function uniqueValues(array $values): array
    {
        return \array_values(\array_unique($values, \SORT_REGULAR));
    }

    /**
     * @return \Generator<int, Configuration>
     */
    private function loadConfigurationsFromProviders(): \Generator
    {
        foreach ($this->providers as $provider) {
            try {
                $result = $this->injector->execute($provider);
            } catch (\Throwable $e) {
                throw new \ErrorException(
                    \sprintf(
                        'An error occurred when executing %s: %s',
                        $this->providerName($provider),
                        $e->getMessage()
                    ),
                    0,
                    1,
                    __FILE__,
                    __LINE__,
                    $e
                );
            }

            if (\is_array($result)) {
                yield $result;
                continue;
            }

            if ($result instanceof \Traversable) {
                yield from $this->validateIterableConfiguration($result, $provider);
                continue;
            }

            throw new \RuntimeException(
                \sprintf(
                    'The provider %s must return an array or iterable of arrays, %s given',
                    $this->providerName($provider),
                    \gettype($result)
                )
            );
        }
    }

    /**
     * @param \Traversable<array-key, mixed> $configuration
     * @param mixed $provider
     * @return \Generator<int, Configuration>
     */
    private function validateIterableConfiguration(\Traversable $configuration, $provider): \Generator
    {
        foreach ($configuration as $item) {
            if (!\is_array($item)) {
                throw new \RuntimeException(
                    \sprintf(
                        'The provider %s yielded %s, expected array',
                        $this->providerName($provider),
                        \gettype($item)
                    )
                );
            }

            yield $item;
        }
    }

    /**
     * @param mixed $provider
     */
    private function providerName($provider): string
    {
        if (\is_object($provider)) {
            return \get_class($provider);
        }

        if (\is_array($provider)) {
            $target = $provider[0] ?? 'unknown';
            $method = $provider[1] ?? 'unknown';

            if (\is_object($target)) {
                $target = \get_class($target);
            }

            return \sprintf('%s::%s', (string)$target, (string)$method);
        }

        if (\is_string($provider)) {
            return $provider;
        }

        return \gettype($provider);
    }
}

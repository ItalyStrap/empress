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
    use ConfigReplacementTrait;

    /**
     * @var ConfigInterface<array-key, mixed>&NodeManipulationInterface<array-key, mixed>
     */
    private ConfigInterface $config;
    private ProvidersCacheInterface $cache;

    /**
     * @param ConfigInterface<array-key, mixed>&NodeManipulationInterface<array-key, mixed> $config
     * @param iterable<Provider> $providers
     */
    public function __construct(
        private Injector $injector,
        ConfigInterface $config,
        ?ProvidersCacheInterface $cache = null,
        private iterable $providers = []
    ) {
        if (!$config instanceof NodeManipulationInterface) {
            throw new \InvalidArgumentException(\sprintf(
                '$config must implement %s',
                NodeManipulationInterface::class
            ));
        }
        $this->config = $config;
        $this->cache = $cache ?? new NullProvidersCache();
    }

    public function aggregate(): void
    {
        if ($this->cache->read($this->config)) {
            return;
        }

        $result = $this->config->toArray();
        foreach ($this->loadConfigurationsFromProviders() as $configuration) {
            $this->mergeConfiguration($configuration, $result);
        }

        $this->replaceConfig($this->config, $result);

        $this->cache->write($this->config);
    }

    /**
     * @param Configuration $configuration
     * @param array<array-key, mixed> $result
     */
    private function mergeConfiguration(
        array $configuration,
        array &$result
    ): void {
        $result = $this->mergeValue($result, $configuration);
    }

    /**
     * @return mixed
     */
    private function mergeValue(mixed $current, mixed $incoming)
    {
        if (!\is_array($incoming)) {
            return $incoming;
        }

        $current = \is_array($current) ? $current : [];

        foreach ($incoming as $key => $value) {
            if (!\is_int($key)) {
                $current[$key] = $this->mergeValue($current[$key] ?? null, $value);
                continue;
            }

            if (!$this->hasListValue($current, $value)) {
                $current[] = $value;
            }
        }

        return $current;
    }

    /**
     * @param array<array-key, mixed> $values
     */
    private function hasListValue(array $values, mixed $valueToFind): bool
    {
        foreach ($values as $key => $value) {
            if (\is_int($key) && $value === $valueToFind) {
                return true;
            }
        }

        return false;
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
                throw new ProviderException(
                    \sprintf(
                        'An error occurred when executing %s: %s',
                        $this->providerName($provider),
                        $e->getMessage()
                    ),
                    0,
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

            throw new ProviderException(
                \sprintf(
                    'The provider %s must return an array or iterable of arrays, %s given',
                    $this->providerName($provider),
                    \get_debug_type($result)
                )
            );
        }
    }

    /**
     * @param \Traversable<array-key, mixed> $configuration
     * @return \Generator<int, Configuration>
     */
    private function validateIterableConfiguration(\Traversable $configuration, mixed $provider): \Generator
    {
        foreach ($configuration as $item) {
            if (!\is_array($item)) {
                throw new ProviderException(
                    \sprintf(
                        'The provider %s yielded %s, expected array',
                        $this->providerName($provider),
                        \get_debug_type($item)
                    )
                );
            }

            yield $item;
        }
    }

    private function providerName(mixed $provider): string
    {
        if (\is_object($provider)) {
            return $provider::class;
        }

        if (\is_array($provider)) {
            $target = $provider[0] ?? 'unknown';
            $method = $provider[1] ?? 'unknown';

            if (\is_object($target)) {
                $target = $target::class;
            }

            return \sprintf('%s::%s', (string)$target, (string)$method);
        }

        if (\is_string($provider)) {
            return $provider;
        }

        return \get_debug_type($provider);
    }
}

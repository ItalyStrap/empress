<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

use Auryn\Injector;
use ItalyStrap\Config\ConfigInterface;
use ItalyStrap\Config\NodeManipulationInterface;

/**
 * @phpstan-type Provider callable|class-string|array{0: class-string|object, 1: non-empty-string}|object
 * @phpstan-type Configuration array<array-key, mixed>
 * @phpstan-type PostProcessor callable(Configuration): Configuration|class-string
 */
final readonly class ProvidersCollection
{
    /**
     * @var ConfigInterface<array-key, mixed>&NodeManipulationInterface<array-key, mixed>
     */
    private ConfigInterface $config;
    private ProvidersCacheInterface $cache;

    /**
     * @param ConfigInterface<array-key, mixed> $config Must also implement NodeManipulationInterface.
     * @param iterable<Provider> $providers
     * @param iterable<PostProcessor> $postProcessors Applied in order to the merged config, before caching it.
     */
    public function __construct(
        private Injector $injector,
        ConfigInterface $config,
        ?ProvidersCacheInterface $cache = null,
        private iterable $providers = [],
        private iterable $postProcessors = []
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
        $cached = $this->cache->read();
        if ($cached !== null) {
            $this->applyToConfig($cached);
            return;
        }

        $result = $this->config->toArray();
        foreach ($this->loadConfigurationsFromProviders() as $configuration) {
            $result = $this->mergeArrays($result, $configuration);
        }

        $result = $this->postProcess($result);

        $this->applyToConfig($result);
        $this->cache->write($result);
    }

    /**
     * @param Configuration $config
     * @return Configuration
     */
    private function postProcess(array $config): array
    {
        foreach ($this->postProcessors as $postProcessor) {
            $processor = \is_string($postProcessor) ? $this->injector->make($postProcessor) : $postProcessor;
            if (!\is_callable($processor)) {
                throw new ProviderException(\sprintf(
                    'The post processor %s is not callable',
                    $this->providerName($postProcessor)
                ));
            }

            $config = $this->assertPostProcessedConfig($processor($config), $postProcessor);
        }

        return $config;
    }

    /**
     * @return Configuration
     */
    private function assertPostProcessedConfig(mixed $result, mixed $postProcessor): array
    {
        if (\is_array($result)) {
            return $result;
        }

        throw new ProviderException(\sprintf(
            'The post processor %s must return an array, %s given',
            $this->providerName($postProcessor),
            \get_debug_type($result)
        ));
    }

    /**
     * Set every top level key, passed as a path so keys containing the delimiter are not split.
     *
     * @param Configuration $values
     */
    private function applyToConfig(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->config->set([$key], $value);
        }
    }

    private function mergeValue(mixed $current, mixed $incoming): mixed
    {
        if (!\is_array($incoming)) {
            return $incoming;
        }

        return $this->mergeArrays(\is_array($current) ? $current : [], $incoming);
    }

    /**
     * String keys are merged recursively, list values are appended once.
     *
     * @param array<array-key, mixed> $current
     * @param array<array-key, mixed> $incoming
     * @return array<array-key, mixed>
     */
    private function mergeArrays(array $current, array $incoming): array
    {
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
        $seen = [];
        foreach ($this->providers as $provider) {
            $this->assertNotRegisteredTwice($provider, $seen);
            $seen[] = $provider;

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

    /**
     * @param list<mixed> $seen
     */
    private function assertNotRegisteredTwice(mixed $provider, array $seen): void
    {
        if (!\in_array($provider, $seen, true)) {
            return;
        }

        throw new ProviderException(\sprintf(
            'The provider %s is registered more than once',
            $this->providerName($provider)
        ));
    }

    private function providerName(mixed $provider): string
    {
        if (\is_array($provider)) {
            return \sprintf(
                '%s::%s',
                $this->describe($provider[0] ?? 'unknown'),
                $this->describe($provider[1] ?? 'unknown')
            );
        }

        return $this->describe($provider);
    }

    /**
     * A string as is, otherwise the class name or the type.
     */
    private function describe(mixed $value): string
    {
        return \is_string($value) ? $value : \get_debug_type($value);
    }
}

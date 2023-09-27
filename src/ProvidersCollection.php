<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

use Auryn\InjectionException;
use Brick\VarExporter\ExportException;
use Brick\VarExporter\VarExporter;
use ItalyStrap\Config\ConfigInterface;
use Webimpress\SafeWriter\Exception\ExceptionInterface as FileWriterException;
use Webimpress\SafeWriter\FileWriter;

/**
 * @psalm-api
 */
class ProvidersCollection
{
    private ConfigInterface $config;
    private Injector $injector;
    private ProvidersCacheInterface $cache;
    /**
     * @var array|callable[]|iterable|string[]
     */
    private iterable $providers;

    /**
     * @param Injector $injector
     * @param ConfigInterface $config
     * @param iterable<class-string|callable> $providers
     * @param ProvidersCacheInterface|null $cache
     * @throws \ErrorException
     */
    public function __construct(
        Injector $injector,
        ConfigInterface $config,
        iterable $providers = [],
        ProvidersCacheInterface $cache = null
    ) {
        $this->injector = $injector;
        $this->config = $config;
        $this->providers = $providers;
        $this->cache = $cache ?? new ProvidersCache();
    }

    public function build(): void
    {
        if ($this->cache->read($this->config)) {
            return;
        }

        $result = [];
        foreach ($this->loadCollectionFromProviders() as $subArray) {
            foreach ($subArray as $key => $value) {
                if (!array_key_exists($key, $result)) {
                    $result[$key] = [];
                }

                if (!is_array($value)) {
                    $result[$key] = $value;
                    continue;
                }

                $result[$key] = \array_merge($result[$key], $value);
            }
        }

        $this->config->merge($result);

        if ($this->config->get(ProvidersCacheInterface::ENABLE_CACHE, false)) {
            $this->cache->write($this->config);
        }
    }

    /**
     * @return ConfigInterface
     */
    public function collection(): ConfigInterface
    {
        return $this->config;
    }

    /**
     * @return array<array-key, mixed>
     * @throws \ErrorException
     */
    private function loadCollectionFromProviders(): array
    {
        $collection = [];
        foreach ($this->providers as $provider) {
            try {
                $result = $this->injector->execute($provider);
            } catch (InjectionException $e) {
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
            } catch (\Throwable $e) {
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
                foreach ($result as $item) {
                    $collection[] = (array)$item;
                }
                continue;
            }

            $collection[] = (array)$result;
        }

        return $collection;
    }
}

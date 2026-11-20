<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

use Auryn\Injector;
use ItalyStrap\Config\Config;
use ItalyStrap\Config\ConfigInterface;
use ItalyStrap\Config\NodeManipulationInterface;
use Psr\Container\ContainerInterface;

final class ContainerBuilder
{
    private readonly Injector $injector;

    /**
     * @var ConfigInterface<array-key, mixed>&NodeManipulationInterface<array-key, mixed>
     */
    private readonly ConfigInterface $config;

    /**
     * @var array<int, callable|class-string|array{0: class-string|object, 1: non-empty-string}|object>
     */
    private array $providers = [];

    /**
     * @var array<int, class-string|Extension>
     */
    private array $extensions = [];

    /**
     * @param ConfigInterface<array-key, mixed>|null $config
     */
    public function __construct(
        ?Injector $injector = null,
        ?ConfigInterface $config = null,
        private readonly ?ProvidersCacheInterface $cache = null,
        private readonly ?ProxyFactoryInterface $proxyFactory = null
    ) {
        if ($config instanceof ConfigInterface && !$config instanceof NodeManipulationInterface) {
            throw new \InvalidArgumentException(\sprintf(
                '$config must implement %s',
                NodeManipulationInterface::class
            ));
        }

        $this->injector = $injector ?: new Injector();
        /** @var ConfigInterface<array-key, mixed>&NodeManipulationInterface<array-key, mixed> $configInstance */
        $configInstance = $config ?: new Config();
        $this->config = $configInstance;
    }

    /**
     * @param callable|class-string|array{0: class-string|object, 1: non-empty-string}|object $provider
     */
    public function addProvider(callable|string|array|object $provider): self
    {
        $this->providers[] = $provider;

        return $this;
    }

    /**
     * @param callable|class-string|array{0: class-string|object, 1: non-empty-string}|object $module
     */
    public function addModule(callable|string|array|object $module): self
    {
        return $this->addProvider($module);
    }

    /**
     * @param class-string|Extension ...$extensions
     */
    public function extend(string|Extension ...$extensions): self
    {
        $this->extensions = [...$this->extensions, ...\array_values($extensions)];
        return $this;
    }

    public function build(): ContainerInterface
    {
        $injector = $this->injector;
        $injector->share($injector);

        $container = new Container($injector);
        $injector->alias(ContainerInterface::class, $container::class);
        $injector->share($container);

        $providersCollection = new ProvidersCollection($injector, $this->config, $this->cache, $this->providers);
        $providersCollection->aggregate();

        $injectorConfig = new AurynConfig($injector, $this->config, $this->proxyFactory);
        $injectorConfig->extend(...$this->extensions);
        $injectorConfig->apply();

        return $container;
    }
}

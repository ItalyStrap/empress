<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

use Auryn\Injector;
use ItalyStrap\Config\Config;
use ItalyStrap\Config\ConfigInterface;
use ItalyStrap\Config\NodeManipulationInterface;
use Psr\Container\ContainerInterface;

class ContainerBuilder
{
    private Injector $injector;

    /**
     * @var ConfigInterface&NodeManipulationInterface
     */
    private ConfigInterface $config;

    private ?ProvidersCacheInterface $cache;

    private ?ProxyFactoryInterface $proxyFactory;

    /**
     * @var array<int, callable|class-string|array{0: class-string|object, 1: non-empty-string}|object>
     */
    private array $providers = [];

    /**
     * @var array<int, class-string|Extension>
     */
    private array $extensions = [];

    public function __construct(
        ?Injector $injector = null,
        ?ConfigInterface $config = null,
        ?ProvidersCacheInterface $cache = null,
        ?ProxyFactoryInterface $proxyFactory = null
    ) {
        if ($config instanceof ConfigInterface && !$config instanceof NodeManipulationInterface) {
            throw new \InvalidArgumentException(\sprintf(
                '$config must implement %s',
                NodeManipulationInterface::class
            ));
        }

        $this->injector = $injector ?: new Injector();
        $this->config = $config ?: new Config();
        $this->cache = $cache;
        $this->proxyFactory = $proxyFactory;
    }

    /**
     * @param callable|class-string|array{0: class-string|object, 1: non-empty-string}|object $provider
     */
    public function addProvider($provider): self
    {
        $this->providers[] = $provider;

        return $this;
    }

    /**
     * @param callable|class-string|array{0: class-string|object, 1: non-empty-string}|object $module
     */
    public function addModule($module): self
    {
        return $this->addProvider($module);
    }

    /**
     * @param class-string|Extension ...$extensions
     */
    public function extend(...$extensions): self
    {
        $this->extensions = $extensions;
        return $this;
    }

    public function build(): ContainerInterface
    {
        $injector = $this->injector;
        $injector->share($injector);

        $container = new Container($injector);
        $injector->alias(ContainerInterface::class, \get_class($container));
        $injector->share($container);

        $providersCollection = new ProvidersCollection($injector, $this->config, $this->cache, $this->providers);
        $providersCollection->aggregate();

        $injectorConfig = new AurynConfig($injector, $this->config, $this->proxyFactory);
        $injectorConfig->extend(...$this->extensions);
        $injectorConfig->apply();

        return $container;
    }
}

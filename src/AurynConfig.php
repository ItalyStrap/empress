<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

use Auryn\ConfigException;
use Auryn\InjectionException;
use ItalyStrap\Config\ConfigInterface as Config;

use function array_walk;

/**
 * @psalm-api
 */
class AurynConfig implements AurynConfigInterface
{
    public const PROXY = 'proxies';
    public const SHARING = 'sharing';
    public const ALIASES = 'aliases';
    public const DEFINITIONS = 'definitions';
    public const DEFINE_PARAM = 'define_param';
    public const DELEGATIONS = 'delegations';
    public const PREPARATIONS = 'preparations';

    private const METHODS = [
        self::PROXY         => 'proxy',
        self::SHARING       => 'share',
        self::ALIASES       => 'alias',
        self::DEFINITIONS   => 'define',
        self::DEFINE_PARAM  => 'defineParam',
        self::DELEGATIONS   => 'delegate',
        self::PREPARATIONS  => 'prepare',
    ];

    private Injector $injector;

    private Config $dependencies;

    /**
     * @var array<Extension>
     */
    private array $extensions = [];

    private ProxyFactoryInterface $proxy_factory;
    private array $extensionsClasses = [];

    /**
     * @param Config $dependencies
     * @param Injector $injector
     */
    public function __construct(
        Injector $injector,
        Config $dependencies,
        ProxyFactoryInterface $proxyFactory = null
    ) {
        $this->injector = $injector;
        $this->dependencies = $dependencies;
        $this->proxy_factory = $proxyFactory ?? new ProxyFactory();
    }

    public function resolve(): void
    {

        /**
         * @var string $key
         * @var callable $method
         */
        foreach (self::METHODS as $key => $method) {
            /** @var callable $callback */
            $callback = [$this, $method];
            $this->walk($key, $callback);
        }

        foreach ($this->extensionsClasses as $extensionClass) {
            $extension = $this->injector->share($extensionClass)->make($extensionClass);
            $extension->execute($this);
        }

        foreach ($this->extensions as $extension) {
            $extension->execute($this);
        }
    }

    public function extendFromClassName(string $className): void
    {
        $this->extensionsClasses[] = $className;
    }

    public function extend(Extension ...$extensions): void
    {
        foreach ($extensions as $extension) {
            $this->extensions[$extension->name()] = $extension;
        }
    }

    public function walk(string $key, callable $callback): void
    {
        /**
         * @var array<array-key, array<string>> $dependencies
         */
        $dependencies = (array)$this->dependencies->get($key, []);
        array_walk($dependencies, $callback, $this->injector);
    }

    /**
     * @param mixed $nameOrInstance
     * @param int $index
     * @throws ConfigException
     * @psalm-suppress PossiblyUnusedParam
     */
    protected function share($nameOrInstance, int $index): void
    {
        $this->injector->share($nameOrInstance);
    }

    /**
     * @param string $name
     * @param int $index
     * @throws ConfigException
     * @psalm-suppress PossiblyUnusedParam
     */
    protected function proxy(string $name, int $index): void
    {
        $this->injector->proxy($name, $this->proxy_factory);
    }

    /**
     * @param string $alias
     * @param string $typeHint
     * @throws ConfigException
     */
    protected function alias(string $alias, string $typeHint): void
    {
        $this->injector->alias($typeHint, $alias);
    }

    /**
     * @param array $class_args
     * @param string $class_name
     */
    protected function define(array $class_args, string $class_name): void
    {
        $this->injector->define($class_name, $class_args);
    }

    /**
     * @param mixed $param_args
     * @param string $param_name
     */
    protected function defineParam($param_args, string $param_name): void
    {
        $this->injector->defineParam($param_name, $param_args);
    }

    /**
     * @param string $callableOrMethodStr
     * @param string $name
     * @throws ConfigException
     */
    protected function delegate($callableOrMethodStr, string $name): void
    {
        $this->injector->delegate($name, $callableOrMethodStr);
    }

    /**
     * @param mixed $callableOrMethodStr
     * @param string $name
     * @throws InjectionException
     */
    protected function prepare($callableOrMethodStr, string $name): void
    {
        $this->injector->prepare($name, $callableOrMethodStr);
    }
}

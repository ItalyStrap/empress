<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

use Auryn\Injector;
use Auryn\ConfigException;
use Auryn\InjectionException;
use ItalyStrap\Config\ConfigInterface as Config;

use function array_walk;

final class AurynConfig implements AurynConfigInterface
{
    public const PROXY = 'proxies';
    public const SHARING = 'sharing';
    public const ALIASES = 'aliases';
    public const DEFINITIONS = 'definitions';
    public const DEFINE_PARAM = 'define_param';
    public const DELEGATIONS = 'delegations';
    public const FACTORIES = 'factories';
    public const PREPARATIONS = 'preparations';

    /**
     * @var array<string, Extension>
     */
    private array $extensions = [];

    /**
     * @var array<array-key, class-string>
     */
    private array $extensionsClasses = [];

    /**
     * @param Config<array-key, mixed> $dependencies
     */
    public function __construct(
        private readonly Injector $injector,
        private readonly Config $dependencies,
        private readonly ?ProxyFactoryInterface $proxy_factory = null
    ) {
    }

    public function apply(): void
    {
        foreach ($this->sectionHandlers() as $key => $handler) {
            $this->walk($key, $handler);
        }

        foreach ($this->extensionsClasses as $extensionClass) {
            /** @var Extension $extension */
            $extension = $this->injector->share($extensionClass)->make($extensionClass);
            $this->extensions[$extension->name()] = $extension;
        }

        foreach ($this->extensions as $extension) {
            $extension->execute($this);
        }
    }

    /**
     * @return array<string, callable>
     */
    private function sectionHandlers(): array
    {
        return [
            self::PROXY        => $this->proxy(...),
            self::SHARING      => $this->share(...),
            self::ALIASES      => $this->alias(...),
            self::DEFINITIONS  => $this->define(...),
            self::DEFINE_PARAM => $this->defineParam(...),
            self::DELEGATIONS  => $this->delegate(...),
            self::FACTORIES    => $this->delegate(...),
            self::PREPARATIONS => $this->prepare(...),
        ];
    }

    /**
     * @deprecated Use apply() instead.
     */
    public function resolve(): void
    {
        $this->apply();
    }

    public function extend(string|Extension ...$extensions): void
    {
        foreach ($extensions as $extension) {
            if (
                \is_string($extension)
                && \class_exists($extension)
                && \is_subclass_of($extension, Extension::class)
            ) {
                $this->extensionsClasses[] = $extension;
                continue;
            }

            if ($extension instanceof Extension) {
                $this->extensions[$extension->name()] = $extension;
                continue;
            }

            throw new \InvalidArgumentException(\sprintf(
                'Invalid extension type, given: %s',
                \gettype($extension)
            ));
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
     * @throws ConfigException
     */
    private function share(mixed $nameOrInstance, int $index): void
    {
        $this->injector->share($nameOrInstance);
    }

    /**
     * @throws ConfigException
     */
    private function proxy(string $name, int $index): void
    {
        if ($name !== '' && \class_exists($name) && $this->proxy_factory === null) {
            throw new ConfigException(\sprintf(
                'Proxy factory is required for proxying %s',
                $name
            ));
        }

        if ($this->proxy_factory === null) {
            return;
        }

        $this->injector->proxy($name, $this->proxy_factory);
    }

    /**
     * @throws ConfigException
     */
    private function alias(string $alias, string $typeHint): void
    {
        $this->injector->alias($typeHint, $alias);
    }

    /**
     * @param array<array-key, mixed> $class_args
     */
    private function define(array $class_args, string $class_name): void
    {
        $this->injector->define($class_name, $class_args);
    }

    private function defineParam(mixed $param_args, string $param_name): void
    {
        $this->injector->defineParam($param_name, $param_args);
    }

    /**
     * @param string $callableOrMethodStr
     * @throws ConfigException
     */
    private function delegate($callableOrMethodStr, string $name): void
    {
        $this->injector->delegate($name, $callableOrMethodStr);
    }

    /**
     * @throws InjectionException
     */
    private function prepare(mixed $callableOrMethodStr, string $name): void
    {
        $this->injector->prepare($name, $callableOrMethodStr);
    }
}

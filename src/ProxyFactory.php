<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

use Closure;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\ValueHolderInterface;
use ProxyManager\Proxy\VirtualProxyInterface;

class ProxyFactory implements ProxyFactoryInterface
{
    /**
     * @psalm-suppress ArgumentTypeCoercion
     */
    public function __invoke(string $className, callable $callback): VirtualProxyInterface
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        return (new LazyLoadingValueHolderFactory())->createProxy(
            $className,
            static function (
                ?object &$object,
                ?object $proxy,
                string $method,
                array $parameters,
                ?Closure &$initializer
            ) use ($callback): bool {
                /** @psalm-suppress MixedAssignment */
                $object = $callback();
                $initializer = null;
                return true;
            }
        );
    }
}

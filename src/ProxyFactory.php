<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

use Closure;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\VirtualProxyInterface;

/**
 * @infection-ignore-all
 */
class ProxyFactory implements ProxyFactoryInterface
{
    /**
     * @param class-string<object> $className
     */
    public function __invoke(string $className, callable $callback): VirtualProxyInterface
    {
        return (new LazyLoadingValueHolderFactory())->createProxy(
            $className,
            static function (
                ?object &$object = null,
                ?object $proxy = null,
                string $method = '',
                array $parameters = [],
                ?Closure &$initializer = null
            ) use ($callback): bool {
                $object = $callback();
                $initializer = null;
                return true;
            }
        );
    }
}

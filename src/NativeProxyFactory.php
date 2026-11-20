<?php

declare(strict_types=1);

namespace ItalyStrap\Empress;

/**
 * Lazy proxies with the native PHP 8.4 lazy objects, no extra dependency.
 */
final class NativeProxyFactory implements ProxyFactoryInterface
{
    /**
     * @param class-string<object> $className
     */
    public function __invoke(string $className, callable $callback): object
    {
        $reflection = new \ReflectionClass($className);
        if (!\method_exists($reflection, 'newLazyProxy')) {
            throw new \LogicException(\sprintf('%s requires PHP 8.4 native lazy objects', self::class));
        }

        return $reflection->newLazyProxy(
            static fn(object $proxy): object => $callback()
        );
    }
}

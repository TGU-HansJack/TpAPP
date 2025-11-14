<?php

namespace TypechoApiPlugin\Extensions;

class API
{
    public static function route(string $method, string $path, $handler, array $middleware = []): void
    {
        ExtensionManager::registerRoute($method, $path, $handler, $middleware);
    }

    public static function middleware(string $alias, string $class): void
    {
        ExtensionManager::registerMiddleware($alias, $class);
    }

    public static function beforeDispatch(callable $callback): void
    {
        ExtensionManager::beforeDispatch($callback);
    }

    public static function afterDispatch(callable $callback): void
    {
        ExtensionManager::afterDispatch($callback);
    }
}

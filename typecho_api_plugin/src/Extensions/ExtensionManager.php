<?php

namespace TypechoApiPlugin\Extensions;

use TypechoApiPlugin\Application;
use TypechoApiPlugin\Http\Request;

class ExtensionManager
{
    private static array $routes = [];
    private static array $middlewares = [];
    private static array $beforeCallbacks = [];
    private static array $afterCallbacks = [];

    public static function boot(): void
    {
        self::$routes = [];
        self::$middlewares = [];
        self::$beforeCallbacks = [];
        self::$afterCallbacks = [];
    }

    public static function reset(): void
    {
        self::$routes = [];
        self::$middlewares = [];
        self::$beforeCallbacks = [];
        self::$afterCallbacks = [];
    }

    public static function registerRoute(string $method, string $path, string $handler, array $middleware = []): void
    {
        self::$routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public static function routes(): array
    {
        return self::$routes;
    }

    public static function registerMiddleware(string $alias, string $class): void
    {
        self::$middlewares[$alias] = $class;
    }

    public static function middlewareAliases(): array
    {
        return self::$middlewares;
    }

    public static function beforeDispatch(callable $callback): void
    {
        self::$beforeCallbacks[] = $callback;
    }

    public static function afterDispatch(callable $callback): void
    {
        self::$afterCallbacks[] = $callback;
    }

    public static function fireBeforeDispatch(Application $app, Request $request): void
    {
        foreach (self::$beforeCallbacks as $callback) {
            call_user_func($callback, $app, $request);
        }
    }

    public static function fireAfterDispatch(Application $app, Request $request, array &$response): void
    {
        foreach (self::$afterCallbacks as $callback) {
            call_user_func_array($callback, [$app, $request, &$response]);
        }
    }
}

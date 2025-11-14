<?php

namespace TypechoApiPlugin\Http;

use Throwable;
use TypechoApiPlugin\Application;
use TypechoApiPlugin\Exceptions\ApiException;
use TypechoApiPlugin\Extensions\ExtensionManager;
use TypechoApiPlugin\Middleware\AdminMiddleware;
use TypechoApiPlugin\Middleware\CorsMiddleware;
use TypechoApiPlugin\Middleware\IpFilterMiddleware;
use TypechoApiPlugin\Middleware\RateLimitMiddleware;
use TypechoApiPlugin\Middleware\RequestLoggerMiddleware;
use TypechoApiPlugin\Middleware\SignatureMiddleware;
use TypechoApiPlugin\Middleware\TokenMiddleware;
use TypechoApiPlugin\Support\Compatibility;
use TypechoApiPlugin\Support\PluginOptions;

class Kernel
{
    public static function bootstrap($archive): void
    {
        $options = PluginOptions::instance();
        $request = new Request(Compatibility::request());

        $prefix = $options->prefix();
        if (!$request->matchesPrefix($prefix)) {
            return;
        }

        if ('OPTIONS' === $request->getMethod()) {
            self::sendPreflight($options);
            exit;
        }

        $app = new Application($options, $request);
        $router = new Router($prefix);

        try {
            ExtensionManager::fireBeforeDispatch($app, $request);
            $route = $router->match($request);
            $request->setRouteParams($route['params']);

            $pipeline = self::buildPipeline(
                $app,
                self::globalMiddleware($options),
                $route['middleware'],
                $route['handler']
            );

            $result = $pipeline($request);

            $payload = is_array($result) && isset($result['code']) ? $result : Response::success($result);
            ExtensionManager::fireAfterDispatch($app, $request, $payload);
            Response::send($payload);
        } catch (ApiException $exception) {
            $payload = Response::error($exception->getCode(), $exception->getMessage(), $exception->getPayload());
            Response::send($payload, $exception->getCode() >= 100 && $exception->getCode() < 600 ? $exception->getCode() : 400);
        } catch (Throwable $throwable) {
            $app->logger()->error('API Fatal Error', [
                'message' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);
            Response::send(Response::error(500, 'internal server error'), 500);
        }

        exit;
    }

    private static function sendPreflight(PluginOptions $options): void
    {
        $headers = [];
        if ($options->bool('allow_cors', true)) {
            $origins = $options->get('cors_origins', '*');
            $headers['Access-Control-Allow-Origin'] = $origins;
            $headers['Access-Control-Allow-Headers'] = 'Authorization, Content-Type, X-Signature, X-Timestamp, X-Nonce';
            $headers['Access-Control-Allow-Methods'] = 'GET,POST,PUT,PATCH,DELETE,OPTIONS';
            $headers['Access-Control-Max-Age'] = '600';
        }

        Response::send(Response::success(), 200, $headers);
    }

    private static function buildPipeline(
        Application $app,
        array $global,
        array $route,
        string $handler
    ): callable {
        $aliases = array_merge(
            self::middlewareAliases($app),
            ExtensionManager::middlewareAliases()
        );

        $middlewares = array_merge($global, $route);

        $controllerInvoker = function (Request $request) use ($app, $handler) {
            if (is_callable($handler)) {
                return call_user_func($handler, $app, $request);
            }

            if (!is_string($handler) || false === strpos($handler, '@')) {
                throw new ApiException('handler not available', 500);
            }

            [$class, $method] = explode('@', $handler);
            if (!class_exists($class)) {
                throw new ApiException('handler not available', 500);
            }

            $controller = new $class($app);
            if (!method_exists($controller, $method)) {
                throw new ApiException('action not found', 404);
            }

            return $controller->{$method}();
        };

        $next = $controllerInvoker;

        foreach (array_reverse($middlewares) as $alias) {
            if (!isset($aliases[$alias])) {
                continue;
            }

            $middleware = new $aliases[$alias]($app);
            $next = function (Request $request) use ($middleware, $next) {
                return $middleware->handle($request, $next);
            };
        }

        return $next;
    }

    private static function middlewareAliases(Application $app): array
    {
        return [
            'cors' => CorsMiddleware::class,
            'logger' => RequestLoggerMiddleware::class,
            'signature' => SignatureMiddleware::class,
            'rate' => RateLimitMiddleware::class,
            'token' => TokenMiddleware::class,
            'admin' => AdminMiddleware::class,
            'ip' => IpFilterMiddleware::class,
        ];
    }

    private static function globalMiddleware(PluginOptions $options): array
    {
        $middlewares = ['ip', 'cors', 'logger'];

        if ($options->bool('enable_signature', false)) {
            $middlewares[] = 'signature';
        }

        return $middlewares;
    }
}

<?php

namespace TypechoApiPlugin\Middleware;

use TypechoApiPlugin\Application;
use TypechoApiPlugin\Http\MiddlewareInterface;
use TypechoApiPlugin\Http\Request;

class RequestLoggerMiddleware implements MiddlewareInterface
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle(Request $request, callable $next)
    {
        if (!$this->app->options()->bool('log_requests', true)) {
            return $next($request);
        }

        $this->app->logger()->info('API Request', [
            'method' => $request->getMethod(),
            'path' => $request->getPath(),
            'ip' => $request->ip(),
        ]);

        $response = $next($request);

        $this->app->logger()->info('API Response', [
            'path' => $request->getPath(),
            'result' => is_array($response) ? $response['code'] ?? 0 : 'raw',
        ]);

        return $response;
    }
}

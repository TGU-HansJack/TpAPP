<?php

namespace TypechoApiPlugin\Middleware;

use TypechoApiPlugin\Application;
use TypechoApiPlugin\Exceptions\ApiException;
use TypechoApiPlugin\Http\MiddlewareInterface;
use TypechoApiPlugin\Http\Request;

class RateLimitMiddleware implements MiddlewareInterface
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle(Request $request, callable $next)
    {
        if (!$this->app->options()->bool('rate_limit_enabled', true)) {
            return $next($request);
        }

        $key = $request->ip() . ':' . $request->getPath();
        $limiter = $this->app->rateLimiter();

        if (!$limiter->hit($key)) {
            $retry = $limiter->retryAfter($key);
            throw new ApiException('too many requests', 429, ['retryAfter' => $retry]);
        }

        return $next($request);
    }
}

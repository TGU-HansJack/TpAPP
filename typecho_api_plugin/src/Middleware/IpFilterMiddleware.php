<?php

namespace TypechoApiPlugin\Middleware;

use TypechoApiPlugin\Application;
use TypechoApiPlugin\Exceptions\ApiException;
use TypechoApiPlugin\Http\MiddlewareInterface;
use TypechoApiPlugin\Http\Request;

class IpFilterMiddleware implements MiddlewareInterface
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle(Request $request, callable $next)
    {
        $blacklist = $this->app->options()->blacklist();
        if (!empty($blacklist) && in_array($request->ip(), $blacklist, true)) {
            throw new ApiException('forbidden', 403);
        }

        return $next($request);
    }
}

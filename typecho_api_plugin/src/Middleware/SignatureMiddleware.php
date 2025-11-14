<?php

namespace TypechoApiPlugin\Middleware;

use TypechoApiPlugin\Application;
use TypechoApiPlugin\Exceptions\ApiException;
use TypechoApiPlugin\Http\MiddlewareInterface;
use TypechoApiPlugin\Http\Request;

class SignatureMiddleware implements MiddlewareInterface
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle(Request $request, callable $next)
    {
        $validator = $this->app->signatureValidator();
        if (!$validator->validate($request)) {
            throw new ApiException('invalid signature', 401);
        }

        return $next($request);
    }
}

<?php

namespace TypechoApiPlugin\Middleware;

use TypechoApiPlugin\Application;
use TypechoApiPlugin\Exceptions\ApiException;
use TypechoApiPlugin\Http\MiddlewareInterface;
use TypechoApiPlugin\Http\Request;
use TypechoApiPlugin\Services\UserService;

class TokenMiddleware implements MiddlewareInterface
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle(Request $request, callable $next)
    {
        $token = $request->bearerToken() ?? $request->query('token');
        if (!$token) {
            throw new ApiException('unauthorized', 401);
        }

        $payload = $this->app->tokenService()->validate($token);
        if (!$payload) {
            throw new ApiException('token expired', 401);
        }

        /** @var UserService $users */
        $users = $this->app->make(UserService::class);
        $user = $users->findById((int) $payload['uid']);
        if (!$user) {
            throw new ApiException('user not found', 401);
        }

        $this->app->setCurrentUser($user);
        return $next($request);
    }
}

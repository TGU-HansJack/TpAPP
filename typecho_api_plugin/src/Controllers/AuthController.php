<?php

namespace TypechoApiPlugin\Controllers;

use TypechoApiPlugin\Exceptions\ApiException;
use TypechoApiPlugin\Services\UserService;

class AuthController extends BaseController
{
    public function login(): array
    {
        $username = $this->input('username');
        $password = $this->input('password');

        if (!$username || !$password) {
            throw new ApiException('用户名和密码必填');
        }

        $user = $this->app->make(UserService::class)->authenticate($username, $password);
        $token = $this->app->tokenService()->issue($user['id']);

        return $this->json([
            'token' => $token['token'],
            'expiredAt' => $token['expiredAt'],
            'user' => $user,
        ]);
    }

    public function refresh(): array
    {
        $token = $this->request->bearerToken() ?? $this->input('token');
        if (!$token) {
            throw new ApiException('token 缺失', 401);
        }

        $refreshed = $this->app->tokenService()->refresh($token);
        if (!$refreshed) {
            throw new ApiException('token 已过期', 401);
        }

        return $this->json($refreshed);
    }
}

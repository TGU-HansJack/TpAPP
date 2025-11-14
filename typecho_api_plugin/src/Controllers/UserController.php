<?php

namespace TypechoApiPlugin\Controllers;

use TypechoApiPlugin\Exceptions\ApiException;
use TypechoApiPlugin\Services\PostsService;
use TypechoApiPlugin\Services\UserService;

class UserController extends BaseController
{
    public function info(): array
    {
        $user = $this->app->user();
        if (!$user) {
            throw new ApiException('unauthorized', 401);
        }

        return $this->json($user);
    }

    public function updateProfile(): array
    {
        $user = $this->app->user();
        $service = $this->app->make(UserService::class);
        $updated = $service->updateProfile($user['id'], [
            'nickname' => $this->input('nickname'),
            'email' => $this->input('email'),
            'url' => $this->input('url'),
            'avatar' => $this->input('avatar'),
        ]);

        $this->app->setCurrentUser($updated);
        return $this->json($updated);
    }

    public function updatePassword(): array
    {
        $user = $this->app->user();
        $old = $this->input('oldPassword');
        $new = $this->input('newPassword');

        if (!$old || !$new) {
            throw new ApiException('请输入旧密码与新密码');
        }

        $this->app->make(UserService::class)->updatePassword($user['id'], $old, $new);
        return $this->json([], '密码已更新');
    }

    public function posts(): array
    {
        $user = $this->app->user();
        $filters = [
            'page' => $this->request->query('page', 1),
            'pageSize' => $this->request->query('pageSize', 10),
            'authorId' => $user['id'],
        ];

        $posts = $this->app->make(PostsService::class)->paginate($filters);
        return $this->json($posts);
    }
}

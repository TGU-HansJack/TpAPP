<?php

namespace TypechoApiPlugin\Controllers;

use TypechoApiPlugin\Exceptions\ApiException;
use TypechoApiPlugin\Services\CommentsService;
use TypechoApiPlugin\Services\UserService;

class CommentsController extends BaseController
{
    public function index(): array
    {
        $cid = (int) $this->request->route('postId');
        $service = $this->app->make(CommentsService::class);
        return $this->json($service->list($cid));
    }

    public function store(): array
    {
        $options = $this->app->options();
        $user = null;

        $token = $this->request->bearerToken();
        if ($token) {
            $payload = $this->app->tokenService()->validate($token);
            if ($payload) {
                $user = $this->app->make(UserService::class)->findById((int) $payload['uid']);
            }
        }

        if ($options->bool('comment_require_login', false) && !$user) {
            throw new ApiException('请先登录后再发表评论', 401);
        }

        $payload = [
            'postId' => $this->input('postId'),
            'author' => $this->input('author'),
            'email' => $this->input('email'),
            'url' => $this->input('url'),
            'content' => $this->input('content'),
            'parentId' => $this->input('parentId'),
        ];

        $comment = $this->app->make(CommentsService::class)->create($payload, $user);
        return $this->json($comment, '评论已提交');
    }
}

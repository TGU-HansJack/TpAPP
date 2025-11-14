<?php

namespace TypechoApiPlugin\Controllers;

use TypechoApiPlugin\Services\CommentsService;

class AdminCommentsController extends BaseController
{
    public function approve(): array
    {
        $id = (int) $this->request->route('id');
        $this->app->make(CommentsService::class)->approve($id);
        return $this->json([], '评论已通过审核');
    }

    public function delete(): array
    {
        $id = (int) $this->request->route('id');
        $this->app->make(CommentsService::class)->delete($id);
        return $this->json([], '评论已删除');
    }
}

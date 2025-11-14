<?php

namespace TypechoApiPlugin\Controllers;

use TypechoApiPlugin\Services\PostsService;

class AdminPostsController extends BaseController
{
    public function create(): array
    {
        $payload = $this->request->all();
        $post = $this->app->make(PostsService::class)->create($payload, $this->app->user());
        return $this->json($post, '文章已发布');
    }

    public function update(): array
    {
        $cid = (int) $this->request->route('id');
        $post = $this->app->make(PostsService::class)->update($cid, $this->request->all());
        return $this->json($post, '文章已更新');
    }

    public function delete(): array
    {
        $cid = (int) $this->request->route('id');
        $this->app->make(PostsService::class)->delete($cid);
        return $this->json([], '文章已删除');
    }

    public function saveDraft(): array
    {
        $payload = $this->request->all();
        $payload['status'] = 'draft';
        $draft = $this->app->make(PostsService::class)->create($payload, $this->app->user());
        return $this->json($draft, '草稿已保存');
    }

    public function updateDraft(): array
    {
        $cid = (int) $this->request->route('id');
        $payload = $this->request->all();
        $payload['status'] = 'draft';
        $draft = $this->app->make(PostsService::class)->update($cid, $payload);
        return $this->json($draft, '草稿已更新');
    }

    public function deleteDraft(): array
    {
        return $this->delete();
    }
}

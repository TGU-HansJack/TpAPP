<?php

namespace TypechoApiPlugin\Controllers;

use TypechoApiPlugin\Services\PostsService;

class PostsController extends BaseController
{
    public function index(): array
    {
        $service = $this->app->make(PostsService::class);
        $filters = [
            'page' => $this->request->query('page', 1),
            'pageSize' => $this->request->query('pageSize', 10),
            'category' => $this->request->query('category'),
            'tag' => $this->request->query('tag'),
            'keyword' => $this->request->query('keyword'),
            'order' => $this->request->query('order', 'created'),
            'includeContent' => $this->request->query('includeContent'),
        ];

        return $this->json($service->paginate($filters));
    }

    public function show(): array
    {
        $identifier = $this->request->route('identifier');
        $service = $this->app->make(PostsService::class);

        $options = [
            'includeContent' => $this->request->query('includeContent'),
            'markdown' => $this->request->query('markdown'),
            'html' => $this->request->query('html'),
        ];

        return $this->json($service->find($identifier, $options));
    }

    public function archive(): array
    {
        $service = $this->app->make(PostsService::class);
        return $this->json($service->getArchive());
    }
}

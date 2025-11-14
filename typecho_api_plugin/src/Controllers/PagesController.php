<?php

namespace TypechoApiPlugin\Controllers;

use TypechoApiPlugin\Services\PagesService;

class PagesController extends BaseController
{
    public function index(): array
    {
        $service = $this->app->make(PagesService::class);
        return $this->json($service->all());
    }

    public function show(): array
    {
        $slug = $this->request->route('slug');
        $service = $this->app->make(PagesService::class);
        return $this->json($service->find($slug));
    }
}

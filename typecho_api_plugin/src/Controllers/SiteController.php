<?php

namespace TypechoApiPlugin\Controllers;

use TypechoApiPlugin\Services\SiteService;

class SiteController extends BaseController
{
    public function info(): array
    {
        $service = $this->app->make(SiteService::class);
        return $this->json($service->info());
    }

    public function author(): array
    {
        $id = (int) $this->request->route('author');
        $service = $this->app->make(SiteService::class);
        return $this->json($service->author($id));
    }

    public function updateConfig(): array
    {
        $service = $this->app->make(SiteService::class);
        return $this->json($service->updateConfig($this->request->all()));
    }
}

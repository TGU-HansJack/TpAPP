<?php

namespace TypechoApiPlugin\Controllers;

use TypechoApiPlugin\Services\TaxonomyService;

class TaxonomyController extends BaseController
{
    public function categories(): array
    {
        $service = $this->app->make(TaxonomyService::class);
        return $this->json($service->categories());
    }

    public function tags(): array
    {
        $service = $this->app->make(TaxonomyService::class);
        return $this->json($service->tags());
    }
}

<?php

namespace TypechoApiPlugin\Controllers;

use TypechoApiPlugin\Services\AdminService;

class StatisticsController extends BaseController
{
    public function index(): array
    {
        $service = $this->app->make(AdminService::class);
        return $this->json($service->statistics());
    }
}

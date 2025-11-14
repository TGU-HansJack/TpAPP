<?php

namespace TypechoApiPlugin\Controllers;

use TypechoApiPlugin\Services\UploadService;

class UploadController extends BaseController
{
    public function store(): array
    {
        $service = $this->app->make(UploadService::class);
        $result = $service->handle($this->request->files(), $this->request->all());
        return $this->json($result, '上传成功');
    }
}

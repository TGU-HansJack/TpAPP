<?php

namespace TypechoApiPlugin\Controllers;

use TypechoApiPlugin\Application;
use TypechoApiPlugin\Http\Request;
use TypechoApiPlugin\Http\Response;

abstract class BaseController
{
    protected Application $app;
    protected Request $request;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->request = $app->request();
    }

    protected function input(string $key, $default = null)
    {
        return $this->request->input($key, $default);
    }

    protected function json($data = [], string $message = 'ok'): array
    {
        return Response::success($data, $message);
    }
}

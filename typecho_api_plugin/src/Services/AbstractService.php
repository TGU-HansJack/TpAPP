<?php

namespace TypechoApiPlugin\Services;

use TypechoApiPlugin\Application;

abstract class AbstractService
{
    protected Application $app;
    protected $db;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->db = $app->db();
    }
}

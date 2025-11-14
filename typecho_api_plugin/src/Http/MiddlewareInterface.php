<?php

namespace TypechoApiPlugin\Http;

interface MiddlewareInterface
{
    public function handle(Request $request, callable $next);
}

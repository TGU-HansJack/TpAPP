<?php

namespace TypechoApiPlugin\Exceptions;

use RuntimeException;

class ApiException extends RuntimeException
{
    private $payload;

    public function __construct(string $message, int $code = 400, $payload = null)
    {
        parent::__construct($message, $code);
        $this->payload = $payload;
    }

    public function getPayload()
    {
        return $this->payload;
    }
}

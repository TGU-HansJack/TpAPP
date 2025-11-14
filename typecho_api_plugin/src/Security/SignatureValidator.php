<?php

namespace TypechoApiPlugin\Security;

use TypechoApiPlugin\Http\Request;
use TypechoApiPlugin\Support\Compatibility;

class SignatureValidator
{
    private string $secret;
    private int $window;

    public function __construct(string $secret, int $window = 300)
    {
        $this->secret = $secret;
        $this->window = $window;
    }

    public function validate(Request $request): bool
    {
        $timestamp = $request->header('x-timestamp') ?? $request->query('timestamp');
        $nonce = $request->header('x-nonce') ?? $request->query('nonce');
        $signature = $request->header('x-signature') ?? $request->query('signature');

        if (!$timestamp || !$nonce || !$signature) {
            return false;
        }

        if (!ctype_digit((string) $timestamp)) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > $this->window) {
            return false;
        }

        $payload = $request->getPath() . $timestamp . $nonce . $this->secret;
        $expected = hash('sha256', $payload);

        return hash_equals($expected, $signature);
    }
}

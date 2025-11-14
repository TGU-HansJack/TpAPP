<?php

namespace TypechoApiPlugin\Security;

class TokenService
{
    private string $secret;
    private int $ttl;

    public function __construct(string $secret, int $ttl)
    {
        $this->secret = $secret;
        $this->ttl = max(60, $ttl);
    }

    public function issue(int $userId, array $extra = []): array
    {
        $payload = array_merge($extra, [
            'uid' => $userId,
            'iat' => time(),
            'exp' => time() + $this->ttl,
            'jti' => bin2hex(random_bytes(8)),
        ]);

        return [
            'token' => $this->encode($payload),
            'expiredAt' => $payload['exp'],
        ];
    }

    public function refresh(string $token): ?array
    {
        $payload = $this->validate($token);
        if (!$payload) {
            return null;
        }

        unset($payload['exp'], $payload['iat'], $payload['jti']);
        return $this->issue((int) $payload['uid'], $payload);
    }

    public function validate(string $token): ?array
    {
        if (!$token || false === strpos($token, '.')) {
            return null;
        }

        [$body, $signature] = explode('.', $token, 2);
        $expected = $this->sign($body);
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $json = base64_decode(strtr($body, '-_', '+/'));
        $payload = json_decode($json, true);
        if (!$payload || ($payload['exp'] ?? 0) < time()) {
            return null;
        }

        return $payload;
    }

    private function encode(array $payload): string
    {
        $body = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        return $body . '.' . $this->sign($body);
    }

    private function sign(string $body): string
    {
        return hash_hmac('sha256', $body, $this->secret);
    }
}

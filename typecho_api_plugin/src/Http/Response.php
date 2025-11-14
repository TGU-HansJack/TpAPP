<?php

namespace TypechoApiPlugin\Http;

class Response
{
    public static function success($data = [], string $message = 'ok', int $code = 0): array
    {
        return [
            'code' => $code,
            'msg' => $message,
            'data' => $data,
            'timestamp' => time(),
        ];
    }

    public static function error(int $code, string $message, $data = null): array
    {
        return [
            'code' => $code,
            'msg' => $message,
            'data' => $data,
            'timestamp' => time(),
        ];
    }

    public static function send(array $payload, int $status = 200, array $headers = []): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8', true, $status);
            foreach ($headers as $name => $value) {
                header($name . ': ' . $value);
            }
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

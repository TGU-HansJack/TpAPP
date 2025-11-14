<?php

namespace TypechoApiPlugin\Http;

use TypechoApiPlugin\Support\Compatibility;

class Request
{
    private $typechoRequest;
    private string $method;
    private string $path;
    private array $query = [];
    private array $payload = [];
    private array $json = [];
    private string $rawBody = '';
    private array $headers = [];
    private array $routeParams = [];

    public function __construct($typechoRequest = null)
    {
        $this->typechoRequest = $typechoRequest ?: Compatibility::request();
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (isset($_POST['_method'])) {
            $override = strtoupper($_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'])) {
                $this->method = $override;
            }
        }

        $this->path = $this->detectPath();
        $this->query = $_GET ?? [];
        $this->headers = $this->detectHeaders();
        $this->parseBody();
    }

    private function detectPath(): string
    {
        $path = null;

        if ($this->typechoRequest && method_exists($this->typechoRequest, 'getPathInfo')) {
            $path = $this->typechoRequest->getPathInfo();
        } elseif (method_exists($this->typechoRequest, 'getRequestUri')) {
            $path = parse_url($this->typechoRequest->getRequestUri(), PHP_URL_PATH);
        }

        if (!$path) {
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        }

        $path = $path ?: '/';
        return '/' . ltrim($path, '/');
    }

    private function detectHeaders(): array
    {
        $headers = [];

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            foreach ($_SERVER as $name => $value) {
                if (0 === strpos($name, 'HTTP_')) {
                    $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $headers[$key] = $value;
                }
            }
        }

        return array_change_key_case($headers, CASE_LOWER);
    }

    private function parseBody(): void
    {
        $this->rawBody = file_get_contents('php://input') ?: '';
        $contentType = $this->header('content-type', '');

        if (false !== stripos($contentType, 'application/json')) {
            $decoded = json_decode($this->rawBody, true);
            if (JSON_ERROR_NONE === json_last_error() && is_array($decoded)) {
                $this->json = $decoded;
            }
        }

        $payload = $_POST ?? [];

        if (!empty($this->json)) {
            $payload = array_merge($payload, $this->json);
        }

        $this->payload = $payload;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function matchesPrefix(string $prefix): bool
    {
        $prefix = '/' . trim($prefix, '/');
        return 0 === strpos($this->path, $prefix);
    }

    public function stripPrefix(string $prefix): string
    {
        $prefix = '/' . trim($prefix, '/');
        if (!$this->matchesPrefix($prefix)) {
            return $this->path;
        }

        $stripped = substr($this->path, strlen($prefix));
        return '/' . ltrim($stripped, '/');
    }

    public function all(): array
    {
        return array_merge($this->query, $this->payload);
    }

    public function input(?string $key = null, $default = null)
    {
        if (null === $key) {
            return $this->payload;
        }

        return $this->payload[$key] ?? $default;
    }

    public function query(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }

    public function header(string $key, $default = null)
    {
        $key = strtolower($key);
        return $this->headers[$key] ?? $default;
    }

    public function json(string $key = null, $default = null)
    {
        if (null === $key) {
            return $this->json;
        }

        return $this->json[$key] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('authorization');
        if (!$header) {
            return null;
        }

        if (preg_match('/Bearer\\s+(.*)$/i', $header, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    public function ip(): string
    {
        $keys = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $value = $_SERVER[$key];
                if (false !== strpos($value, ',')) {
                    $value = trim(explode(',', $value)[0]);
                }
                return $value;
            }
        }

        return '0.0.0.0';
    }

    public function files(): array
    {
        return $_FILES ?? [];
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function route(string $key, $default = null)
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function wantsJson(): bool
    {
        $accept = $this->header('accept');
        return !$accept || false !== strpos($accept, 'application/json');
    }

    public function rawBody(): string
    {
        return $this->rawBody;
    }
}

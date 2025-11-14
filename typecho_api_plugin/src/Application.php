<?php

namespace TypechoApiPlugin;

use TypechoApiPlugin\Http\Request;
use TypechoApiPlugin\Logging\Logger;
use TypechoApiPlugin\Security\RateLimiter;
use TypechoApiPlugin\Security\SignatureValidator;
use TypechoApiPlugin\Security\TokenService;
use TypechoApiPlugin\Support\Compatibility;
use TypechoApiPlugin\Support\PluginOptions;

class Application
{
    private PluginOptions $options;
    private Request $request;
    private array $resolved = [];
    private ?array $currentUser = null;

    public function __construct(PluginOptions $options, Request $request)
    {
        $this->options = $options;
        $this->request = $request;
    }

    public function options(): PluginOptions
    {
        return $this->options;
    }

    public function request(): Request
    {
        return $this->request;
    }

    public function db()
    {
        return Compatibility::db();
    }

    public function logger(): Logger
    {
        if (!isset($this->resolved[Logger::class])) {
            $this->resolved[Logger::class] = new Logger($this);
        }

        return $this->resolved[Logger::class];
    }

    public function tokenService(): TokenService
    {
        if (!isset($this->resolved[TokenService::class])) {
            $this->resolved[TokenService::class] = new TokenService(
                $this->options->tokenSecret(),
                $this->options->tokenTtl()
            );
        }

        return $this->resolved[TokenService::class];
    }

    public function signatureValidator(): SignatureValidator
    {
        if (!isset($this->resolved[SignatureValidator::class])) {
            $this->resolved[SignatureValidator::class] = new SignatureValidator($this->options->signatureSecret());
        }

        return $this->resolved[SignatureValidator::class];
    }

    public function rateLimiter(): RateLimiter
    {
        if (!isset($this->resolved[RateLimiter::class])) {
            $path = $this->storagePath('cache/rate-limit.json');
            $this->resolved[RateLimiter::class] = new RateLimiter(
                $path,
                $this->options->rateLimitRequests(),
                $this->options->rateLimitInterval()
            );
        }

        return $this->resolved[RateLimiter::class];
    }

    public function storagePath(string $relative = ''): string
    {
        $base = TYPECHO_API_PLUGIN_ROOT . '/runtime';
        if (!is_dir($base)) {
            mkdir($base, 0777, true);
        }

        if ($relative === '') {
            return $base;
        }

        $path = $base . '/' . ltrim($relative, '/');
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        return $path;
    }

    public function make(string $class)
    {
        if (!isset($this->resolved[$class])) {
            $this->resolved[$class] = new $class($this);
        }

        return $this->resolved[$class];
    }

    public function setCurrentUser(array $user): void
    {
        $this->currentUser = $user;
    }

    public function user(): ?array
    {
        return $this->currentUser;
    }
}

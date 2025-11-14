<?php

namespace TypechoApiPlugin\Logging;

use TypechoApiPlugin\Application;

class Logger
{
    private string $logFile;
    private bool $enabled;

    public function __construct(Application $app)
    {
        $this->logFile = $app->storagePath('logs/api.log');
        $this->enabled = $app->options()->bool('log_requests', true);
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write('WARNING', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    private function write(string $level, string $message, array $context): void
    {
        if (!$this->enabled) {
            return;
        }

        $payload = $context ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        $line = sprintf("[%s][%s] %s %s\n", date('Y-m-d H:i:s'), $level, $message, $payload);
        file_put_contents($this->logFile, $line, FILE_APPEND);
    }
}

<?php

namespace TypechoApiPlugin\Support;

use TypechoApiPlugin\Application;

class UserMetaRepository
{
    private string $path;
    private array $data = [];
    private bool $dirty = false;

    public function __construct(Application $app)
    {
        $this->path = $app->storagePath('cache/user-meta.json');
        $this->data = $this->read();
        register_shutdown_function([$this, 'persist']);
    }

    public function get(int $uid, string $key, $default = null)
    {
        return $this->data[$uid][$key] ?? $default;
    }

    public function set(int $uid, string $key, $value): void
    {
        if (!isset($this->data[$uid])) {
            $this->data[$uid] = [];
        }

        $this->data[$uid][$key] = $value;
        $this->dirty = true;
    }

    private function read(): array
    {
        if (!is_file($this->path)) {
            return [];
        }

        $contents = file_get_contents($this->path);
        $decoded = json_decode($contents, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function persist(): void
    {
        if (!$this->dirty) {
            return;
        }

        file_put_contents($this->path, json_encode($this->data));
        $this->dirty = false;
    }
}

<?php

namespace TypechoApiPlugin\Security;

class RateLimiter
{
    private string $storage;
    private int $limit;
    private int $interval;

    public function __construct(string $storage, int $limit, int $interval)
    {
        $this->storage = $storage;
        $this->limit = max(1, $limit);
        $this->interval = max(1, $interval);
    }

    public function hit(string $key): bool
    {
        $now = time();
        $data = $this->read();
        $bucket = $data[$key] ?? ['count' => 0, 'reset' => $now + $this->interval];

        if ($bucket['reset'] <= $now) {
            $bucket = ['count' => 0, 'reset' => $now + $this->interval];
        }

        if ($bucket['count'] >= $this->limit) {
            $data[$key] = $bucket;
            $this->write($data);
            return false;
        }

        $bucket['count']++;
        $data[$key] = $bucket;
        $this->write($data);

        return true;
    }

    public function remaining(string $key): int
    {
        $data = $this->read();
        if (!isset($data[$key])) {
            return $this->limit;
        }

        return max(0, $this->limit - $data[$key]['count']);
    }

    public function retryAfter(string $key): int
    {
        $data = $this->read();
        if (!isset($data[$key])) {
            return 0;
        }

        return max(0, $data[$key]['reset'] - time());
    }

    private function read(): array
    {
        if (!is_file($this->storage)) {
            return [];
        }

        $contents = file_get_contents($this->storage);
        $decoded = json_decode($contents, true);
        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    private function write(array $data): void
    {
        file_put_contents($this->storage, json_encode($data), LOCK_EX);
    }
}

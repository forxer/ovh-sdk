<?php

declare(strict_types=1);

namespace Tests\Support;

use DateInterval;
use OvhSdk\Support\SystemClock;
use Psr\Clock\ClockInterface;
use Psr\SimpleCache\CacheInterface;

final class ArrayCache implements CacheInterface
{
    /** @var array<string, array{value: mixed, expiresAt: int|null}> */
    private array $items = [];

    private readonly ClockInterface $clock;

    public function __construct(?ClockInterface $clock = null)
    {
        $this->clock = $clock ?? new SystemClock;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (! $this->has($key)) {
            return $default;
        }

        return $this->items[$key]['value'];
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $now = $this->clock->now();

        if ($ttl instanceof DateInterval) {
            $expiresAt = $now->add($ttl)->getTimestamp();
        } elseif ($ttl !== null) {
            $expiresAt = $now->getTimestamp() + $ttl;
        } else {
            $expiresAt = null;
        }

        $this->items[$key] = ['value' => $value, 'expiresAt' => $expiresAt];

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->items[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->items = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }

        return $values;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set((string) $key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        if (! isset($this->items[$key])) {
            return false;
        }

        $expiresAt = $this->items[$key]['expiresAt'];

        if ($expiresAt !== null && $expiresAt <= $this->clock->now()->getTimestamp()) {
            unset($this->items[$key]);

            return false;
        }

        return true;
    }
}

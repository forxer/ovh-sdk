<?php

declare(strict_types=1);

namespace OvhSdk\Resources;

use RuntimeException;
use Saloon\Http\BaseResource;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Throwable;

abstract class Resource extends BaseResource
{
    /** How many requests sendAll() keeps in flight at once. */
    protected const int CONCURRENCY = 5;

    /**
     * Sends the requests through a Saloon pool and returns their DTOs in the order of $requests.
     * Throws the first exception raised by any request: never a partial list.
     *
     * Every request is awaited before throwing. When several fail, the exception of the earliest request in
     * $requests is thrown, whatever the order the responses arrived in, so that a failure is reproducible.
     *
     * @param  list<Request>  $requests
     * @return list<mixed>
     *
     * @throws Throwable
     */
    protected function sendAll(array $requests): array
    {
        /** @var array<int|string, mixed> $results */
        $results = [];

        /** @var array<int|string, Throwable> $failures */
        $failures = [];

        $pool = $this->connector->pool(
            $requests,
            static::CONCURRENCY,
            static function (Response $response, int|string $key) use (&$results, &$failures): void {
                try {
                    $results[$key] = $response->dto();
                } catch (Throwable $exception) {
                    $failures[$key] = $exception;
                }
            },
            static function (mixed $reason, int|string $key) use (&$failures): void {
                // Saloon always rejects with an exception; anything else is wrapped so that it can be thrown.
                $failures[$key] = $reason instanceof Throwable
                    ? $reason
                    : new RuntimeException('A pooled OVH request failed without an exception.');
            },
        );

        $pool->send()->wait();

        if ($failures !== []) {
            ksort($failures);

            throw reset($failures);
        }

        ksort($results);

        return array_values($results);
    }
}

<?php

declare(strict_types=1);

namespace OvhSdk;

use OvhSdk\Enums\Region;
use OvhSdk\Exceptions\OvhException;
use OvhSdk\Requests\Auth\GetServerTime;
use OvhSdk\Requests\Generic\GenericJsonRequest;
use OvhSdk\Requests\Generic\GenericRequest;
use OvhSdk\Support\SystemClock;
use Psr\Clock\ClockInterface;
use Psr\SimpleCache\CacheInterface;
use Saloon\Contracts\Authenticator;
use Saloon\Enums\Method;
use Saloon\Http\Connector;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use Throwable;

final class Ovh extends Connector
{
    use AlwaysThrowOnErrors;

    private const int TIME_DELTA_TTL = 3600;

    private ?int $timeDelta = null;

    public function __construct(
        public readonly Region $region,
        Authenticator $credentials,
        public readonly ?CacheInterface $cache = null,
        public readonly ClockInterface $clock = new SystemClock,
    ) {
        $this->authenticate($credentials);
    }

    public function resolveBaseUrl(): string
    {
        return $this->region->apiUrl();
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function get(string $path, array $query = []): Response
    {
        return $this->send(new GenericRequest(Method::GET, $path, $query));
    }

    /**
     * @param  array<array-key, mixed>  $body
     */
    public function post(string $path, array $body = []): Response
    {
        return $this->sendWithBody(Method::POST, $path, $body);
    }

    /**
     * @param  array<array-key, mixed>  $body
     */
    public function put(string $path, array $body = []): Response
    {
        return $this->sendWithBody(Method::PUT, $path, $body);
    }

    public function delete(string $path): Response
    {
        return $this->send(new GenericRequest(Method::DELETE, $path));
    }

    /**
     * Seconds to add to the local clock to get the OVHcloud server time.
     *
     * Computed once per instance, shared through the PSR-16 cache when one is given.
     */
    public function timeDelta(): int
    {
        if ($this->timeDelta !== null) {
            return $this->timeDelta;
        }

        $key = 'ovh-sdk.time-delta.'.$this->region->value;
        $cached = $this->cache?->get($key);

        if (\is_int($cached)) {
            return $this->timeDelta = $cached;
        }

        /** @var int $serverTime */
        $serverTime = $this->send(new GetServerTime)->dto();
        $delta = $serverTime - $this->clock->now()->getTimestamp();
        $this->cache?->set($key, $delta, self::TIME_DELTA_TTL);

        return $this->timeDelta = $delta;
    }

    public function getRequestException(Response $response, ?Throwable $senderException): Throwable
    {
        return OvhException::fromResponse($response, $senderException);
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return ['Accept' => 'application/json'];
    }

    /**
     * @param  array<array-key, mixed>  $body
     */
    private function sendWithBody(Method $method, string $path, array $body): Response
    {
        return $this->send($body === []
            ? new GenericRequest($method, $path)
            : new GenericJsonRequest($method, $path, $body));
    }
}

<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Auth;

use OvhSdk\Requests\OvhRequest;
use Saloon\Contracts\Authenticator;
use Saloon\Enums\Method;
use Saloon\Http\Auth\NullAuthenticator;
use Saloon\Http\Response;

/**
 * Unix time of the OVHcloud servers. Sent unauthenticated: it is needed to sign the other requests.
 */
final class GetServerTime extends OvhRequest
{
    protected Method $method = Method::GET;

    public function createDtoFromResponse(Response $response): int
    {
        return (int) trim($response->body());
    }

    protected function path(): string
    {
        return '/auth/time';
    }

    protected function defaultAuth(): Authenticator
    {
        return new NullAuthenticator;
    }
}

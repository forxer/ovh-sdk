<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Auth;

use OvhSdk\Exceptions\CouldNotReadResponse;
use OvhSdk\Requests\OvhRequest;
use OvhSdk\Support\Json;
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

    /**
     * OVHcloud sends the time as a JSON integer. Anything else throws rather than giving a time delta that would
     * break the signature of every request for as long as it is cached.
     *
     * @throws CouldNotReadResponse
     */
    public function createDtoFromResponse(Response $response): int
    {
        $time = Json::decode($response);

        return \is_int($time) ? $time : throw CouldNotReadResponse::notAnInteger('server time');
    }

    protected function path(): string
    {
        return '/auth/time';
    }

    protected function defaultAuth(): Authenticator
    {
        return new NullAuthenticator();
    }
}

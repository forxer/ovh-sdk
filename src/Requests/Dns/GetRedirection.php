<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Dns;

use OvhSdk\Data\Dns\Redirection;
use OvhSdk\Requests\OvhRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * A web redirection of a DNS zone.
 */
final class GetRedirection extends OvhRequest
{
    protected Method $method = Method::GET;

    public function __construct(public readonly string $zoneName, public readonly int $id) {}

    public function createDtoFromResponse(Response $response): Redirection
    {
        return Redirection::fromArray($response->json());
    }

    protected function path(): string
    {
        return '/domain/zone/'.rawurlencode($this->zoneName).'/redirection/'.$this->id;
    }
}

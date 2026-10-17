<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Dns;

use OvhSdk\Data\Dns\Zone;
use OvhSdk\Requests\OvhRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * Properties of a DNS zone.
 */
final class GetZone extends OvhRequest
{
    protected Method $method = Method::GET;

    public function __construct(public readonly string $zoneName) {}

    public function createDtoFromResponse(Response $response): Zone
    {
        return Zone::fromArray($response->json());
    }

    protected function path(): string
    {
        return '/domain/zone/'.rawurlencode($this->zoneName);
    }
}

<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Dns;

use OvhSdk\Data\Dns\ZoneStatus;
use OvhSdk\Requests\OvhRequest;
use OvhSdk\Support\Json;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * Deployment status of a DNS zone, with its errors and warnings.
 */
final class GetZoneStatus extends OvhRequest
{
    protected Method $method = Method::GET;

    public function __construct(public readonly string $zoneName) {}

    public function createDtoFromResponse(Response $response): ZoneStatus
    {
        return ZoneStatus::fromArray(Json::object($response, 'zone status'));
    }

    protected function path(): string
    {
        return '/domain/zone/'.rawurlencode($this->zoneName).'/status';
    }
}

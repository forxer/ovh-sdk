<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Dns;

use OvhSdk\Data\Dns\ZoneTask;
use OvhSdk\Requests\OvhRequest;
use OvhSdk\Support\Json;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * A task of a DNS zone.
 */
final class GetZoneTask extends OvhRequest
{
    protected Method $method = Method::GET;

    public function __construct(public readonly string $zoneName, public readonly int $id) {}

    public function createDtoFromResponse(Response $response): ZoneTask
    {
        return ZoneTask::fromArray(Json::object($response, 'zone task'));
    }

    protected function path(): string
    {
        return '/domain/zone/'.rawurlencode($this->zoneName).'/task/'.$this->id;
    }
}

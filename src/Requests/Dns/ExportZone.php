<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Dns;

use OvhSdk\Exceptions\CouldNotReadResponse;
use OvhSdk\Requests\OvhRequest;
use OvhSdk\Support\Json;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * Content of a DNS zone, as a BIND zone file.
 */
final class ExportZone extends OvhRequest
{
    protected Method $method = Method::GET;

    public function __construct(public readonly string $zoneName) {}

    /**
     * OVHcloud sends the zone file as a JSON string.
     *
     * @throws CouldNotReadResponse
     */
    public function createDtoFromResponse(Response $response): string
    {
        $zoneFile = Json::decode($response);

        return \is_string($zoneFile) ? $zoneFile : throw CouldNotReadResponse::notAString('zone export');
    }

    protected function path(): string
    {
        return '/domain/zone/'.rawurlencode($this->zoneName).'/export';
    }
}

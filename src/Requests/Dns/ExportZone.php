<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Dns;

use JsonException;
use OvhSdk\Exceptions\CouldNotReadResponse;
use OvhSdk\Requests\OvhRequest;
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
     * OVHcloud sends the zone file as a JSON string, which Saloon's Response::json() cannot decode as it only
     * accepts arrays: the body is decoded here.
     *
     * @throws JsonException
     * @throws CouldNotReadResponse
     */
    public function createDtoFromResponse(Response $response): string
    {
        $zoneFile = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

        return \is_string($zoneFile) ? $zoneFile : throw CouldNotReadResponse::notAString('zone export');
    }

    protected function path(): string
    {
        return '/domain/zone/'.rawurlencode($this->zoneName).'/export';
    }
}

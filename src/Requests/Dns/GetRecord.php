<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Dns;

use OvhSdk\Data\Dns\Record;
use OvhSdk\Requests\OvhRequest;
use OvhSdk\Support\Json;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * A record of a DNS zone.
 */
final class GetRecord extends OvhRequest
{
    protected Method $method = Method::GET;

    public function __construct(public readonly string $zoneName, public readonly int $id) {}

    public function createDtoFromResponse(Response $response): Record
    {
        return Record::fromArray(Json::object($response, 'record'));
    }

    protected function path(): string
    {
        return '/domain/zone/'.rawurlencode($this->zoneName).'/record/'.$this->id;
    }
}

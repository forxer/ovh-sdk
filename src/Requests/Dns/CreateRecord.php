<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Dns;

use OvhSdk\Data\Dns\Record;
use OvhSdk\Enums\RecordType;
use OvhSdk\Requests\OvhRequest;
use OvhSdk\Support\Json;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Creates a record in a DNS zone. The zone must be refreshed for the record to be served.
 */
final class CreateRecord extends OvhRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        public readonly string $zoneName,
        public readonly RecordType $type,
        public readonly string $target,
        public readonly ?string $subDomain = null,
        public readonly ?int $ttl = null,
    ) {}

    public function createDtoFromResponse(Response $response): Record
    {
        return Record::fromArray(Json::object($response, 'record'));
    }

    protected function path(): string
    {
        return '/domain/zone/'.rawurlencode($this->zoneName).'/record';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        $body = ['fieldType' => $this->type->value, 'target' => $this->target];

        if ($this->subDomain !== null) {
            $body['subDomain'] = $this->subDomain;
        }

        if ($this->ttl !== null) {
            $body['ttl'] = $this->ttl;
        }

        return $body;
    }
}

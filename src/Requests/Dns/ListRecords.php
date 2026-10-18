<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Dns;

use OvhSdk\Enums\RecordType;
use OvhSdk\Exceptions\CouldNotReadResponse;
use OvhSdk\Requests\OvhRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * Identifiers of the records of a DNS zone, optionally filtered by type and sub-domain.
 */
final class ListRecords extends OvhRequest
{
    protected Method $method = Method::GET;

    public function __construct(
        public readonly string $zoneName,
        public readonly ?RecordType $type = null,
        public readonly ?string $subDomain = null,
    ) {}

    /**
     * @return list<int>
     *
     * @throws CouldNotReadResponse
     */
    public function createDtoFromResponse(Response $response): array
    {
        $ids = $response->json();

        if (! array_is_list($ids)) {
            throw CouldNotReadResponse::notAList('record list');
        }

        return array_map(
            static fn (mixed $id): int => \is_int($id) ? $id : throw CouldNotReadResponse::invalidListItem('record list', 'an integer'),
            $ids,
        );
    }

    protected function path(): string
    {
        return '/domain/zone/'.rawurlencode($this->zoneName).'/record';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        $query = [];

        if ($this->type !== null) {
            $query['fieldType'] = $this->type->value;
        }

        if ($this->subDomain !== null) {
            $query['subDomain'] = $this->subDomain;
        }

        return $query;
    }
}

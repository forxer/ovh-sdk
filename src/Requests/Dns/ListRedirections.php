<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Dns;

use OvhSdk\Exceptions\CouldNotReadResponse;
use OvhSdk\Requests\OvhRequest;
use OvhSdk\Support\Json;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * Identifiers of the web redirections of a DNS zone, optionally filtered by sub-domain.
 */
final class ListRedirections extends OvhRequest
{
    protected Method $method = Method::GET;

    public function __construct(public readonly string $zoneName, public readonly ?string $subDomain = null) {}

    /**
     * @return list<int>
     *
     * @throws CouldNotReadResponse
     */
    public function createDtoFromResponse(Response $response): array
    {
        $ids = Json::list($response, 'redirection list');

        return array_map(
            static fn (mixed $id): int => \is_int($id) ? $id : throw CouldNotReadResponse::invalidListItem('redirection list', 'an integer'),
            $ids,
        );
    }

    protected function path(): string
    {
        return '/domain/zone/'.rawurlencode($this->zoneName).'/redirection';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        return $this->subDomain === null ? [] : ['subDomain' => $this->subDomain];
    }
}

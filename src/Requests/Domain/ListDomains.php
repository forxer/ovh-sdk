<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Domain;

use OvhSdk\Exceptions\CouldNotReadResponse;
use OvhSdk\Requests\OvhRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * Names of the domains of the account.
 */
final class ListDomains extends OvhRequest
{
    protected Method $method = Method::GET;

    /**
     * @return list<string>
     *
     * @throws CouldNotReadResponse
     */
    public function createDtoFromResponse(Response $response): array
    {
        $names = $response->json();

        if (! array_is_list($names)) {
            throw CouldNotReadResponse::notAList('domain list');
        }

        return array_map(
            static fn (mixed $name): string => \is_string($name) ? $name : throw CouldNotReadResponse::invalidListItem('domain list', 'a string'),
            $names,
        );
    }

    protected function path(): string
    {
        return '/domain';
    }
}

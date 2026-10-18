<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Dns;

use OvhSdk\Exceptions\CouldNotReadResponse;
use OvhSdk\Requests\OvhRequest;
use OvhSdk\Support\Json;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * Names of the DNS zones of the account.
 */
final class ListZones extends OvhRequest
{
    protected Method $method = Method::GET;

    /**
     * @return list<string>
     *
     * @throws CouldNotReadResponse
     */
    public function createDtoFromResponse(Response $response): array
    {
        $names = Json::list($response, 'zone list');

        return array_map(
            static fn (mixed $name): string => \is_string($name) ? $name : throw CouldNotReadResponse::invalidListItem('zone list', 'a string'),
            $names,
        );
    }

    protected function path(): string
    {
        return '/domain/zone';
    }
}

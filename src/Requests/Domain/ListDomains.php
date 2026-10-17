<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Domain;

use OvhSdk\Requests\OvhRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use UnexpectedValueException;

/**
 * Names of the domains of the account.
 */
final class ListDomains extends OvhRequest
{
    protected Method $method = Method::GET;

    /**
     * @return list<string>
     *
     * @throws UnexpectedValueException
     */
    public function createDtoFromResponse(Response $response): array
    {
        $names = $response->json();

        if (! array_is_list($names)) {
            throw new UnexpectedValueException('OVH domain list response is not a list.');
        }

        return array_map(
            static fn (mixed $name): string => \is_string($name) ? $name : throw new UnexpectedValueException('OVH domain list response holds a value that is not a string.'),
            $names,
        );
    }

    protected function path(): string
    {
        return '/domain';
    }
}

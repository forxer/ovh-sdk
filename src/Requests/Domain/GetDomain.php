<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Domain;

use OvhSdk\Data\Domain\Domain;
use OvhSdk\Requests\OvhRequest;
use OvhSdk\Support\Json;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * Properties of a domain.
 */
final class GetDomain extends OvhRequest
{
    protected Method $method = Method::GET;

    public function __construct(public readonly string $domain) {}

    public function createDtoFromResponse(Response $response): Domain
    {
        return Domain::fromArray(Json::object($response, 'domain'));
    }

    protected function path(): string
    {
        return '/domain/'.rawurlencode($this->domain);
    }
}

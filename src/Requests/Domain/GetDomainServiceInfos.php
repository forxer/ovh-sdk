<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Domain;

use OvhSdk\Data\Service\ServiceInfos;
use OvhSdk\Requests\OvhRequest;
use OvhSdk\Support\Json;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * Service information of a domain: expiration, creation and renewal settings.
 */
final class GetDomainServiceInfos extends OvhRequest
{
    protected Method $method = Method::GET;

    public function __construct(public readonly string $domain) {}

    public function createDtoFromResponse(Response $response): ServiceInfos
    {
        return ServiceInfos::fromArray(Json::object($response, 'domain service information'));
    }

    protected function path(): string
    {
        return '/domain/'.rawurlencode($this->domain).'/serviceInfos';
    }
}

<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Dns;

use OvhSdk\Requests\OvhRequest;
use Saloon\Enums\Method;

/**
 * Applies the pending changes of a DNS zone. Sends no body.
 */
final class RefreshZone extends OvhRequest
{
    protected Method $method = Method::POST;

    public function __construct(public readonly string $zoneName) {}

    protected function path(): string
    {
        return '/domain/zone/'.rawurlencode($this->zoneName).'/refresh';
    }
}

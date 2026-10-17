<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Dns;

use OvhSdk\Requests\OvhRequest;
use Saloon\Enums\Method;

/**
 * Deletes a record of a DNS zone.
 */
final class DeleteRecord extends OvhRequest
{
    protected Method $method = Method::DELETE;

    public function __construct(public readonly string $zoneName, public readonly int $id) {}

    protected function path(): string
    {
        return '/domain/zone/'.rawurlencode($this->zoneName).'/record/'.$this->id;
    }
}

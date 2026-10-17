<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Dns;

use OvhSdk\Requests\OvhRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Updates a record of a DNS zone. Only the given fields are sent.
 */
final class UpdateRecord extends OvhRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    /**
     * @param  array<string, mixed>  $changes
     */
    public function __construct(
        public readonly string $zoneName,
        public readonly int $id,
        public readonly array $changes,
    ) {}

    protected function path(): string
    {
        return '/domain/zone/'.rawurlencode($this->zoneName).'/record/'.$this->id;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->changes;
    }
}

<?php

declare(strict_types=1);

namespace OvhSdk\Data\Domain;

use OvhSdk\Exceptions\CouldNotReadResponse;
use OvhSdk\Support\Fields;

/**
 * Schema domain.nameServer.NameServer.
 */
final readonly class NameServer
{
    public function __construct(
        public string $nameServer,
        public ?string $ipv4,
        public ?string $ipv6,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     *
     * @throws CouldNotReadResponse
     */
    public static function fromArray(array $data): self
    {
        return new self(
            nameServer: Fields::string($data, 'nameServer'),
            ipv4: Fields::nullableString($data, 'ipv4'),
            ipv6: Fields::nullableString($data, 'ipv6'),
        );
    }
}

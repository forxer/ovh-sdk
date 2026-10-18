<?php

declare(strict_types=1);

namespace OvhSdk\Data\Dns;

use DateTimeImmutable;
use OvhSdk\Exceptions\CouldNotReadResponse;
use OvhSdk\Support\Dates;
use OvhSdk\Support\Fields;

/**
 * Schema domain.Zone, reduced to the properties below: the IAM metadata is ignored.
 */
final readonly class Zone
{
    /**
     * @param  list<string>  $nameServers
     */
    public function __construct(
        public string $name,
        public array $nameServers,
        public ?DateTimeImmutable $lastUpdate,
        public bool $dnssecSupported,
        public bool $dnssecActivated,
        public bool $hasDnsAnycast,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     *
     * @throws CouldNotReadResponse
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: Fields::string($data, 'name'),
            nameServers: Fields::stringList($data, 'nameServers'),
            lastUpdate: Dates::parseNullable(Fields::nullableString($data, 'lastUpdate')),
            dnssecSupported: Fields::bool($data, 'dnssecSupported'),
            dnssecActivated: Fields::bool($data, 'dnssecActivated'),
            hasDnsAnycast: Fields::bool($data, 'hasDnsAnycast'),
        );
    }
}

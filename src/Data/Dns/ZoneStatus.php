<?php

declare(strict_types=1);

namespace OvhSdk\Data\Dns;

use DateTimeImmutable;
use OvhSdk\Exceptions\CouldNotReadResponse;
use OvhSdk\Support\Dates;
use OvhSdk\Support\Fields;

/**
 * Schema domain.zone.Status, plus the "lastRefresh" property that OVHcloud sends but the schema does not declare.
 */
final readonly class ZoneStatus
{
    /**
     * @param  list<string>|null  $errors
     * @param  list<string>|null  $warnings
     */
    public function __construct(
        public bool $isDeployed,
        public ?array $errors,
        public ?array $warnings,
        public ?DateTimeImmutable $lastRefresh,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     *
     * @throws CouldNotReadResponse
     */
    public static function fromArray(array $data): self
    {
        return new self(
            isDeployed: Fields::bool($data, 'isDeployed'),
            errors: Fields::nullableStringList($data, 'errors'),
            warnings: Fields::nullableStringList($data, 'warnings'),
            lastRefresh: Dates::parseNullable(Fields::nullableString($data, 'lastRefresh')),
        );
    }
}

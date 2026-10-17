<?php

declare(strict_types=1);

namespace OvhSdk\Data\Dns;

use OvhSdk\Enums\RecordType;
use OvhSdk\Support\Fields;
use UnexpectedValueException;

/**
 * Schema domain.zone.Record. The "fieldType" property is exposed as $type.
 */
final readonly class Record
{
    public function __construct(
        public int $id,
        public string $zone,
        public ?string $subDomain,
        public RecordType $type,
        public string $target,
        public ?int $ttl,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     *
     * @throws UnexpectedValueException
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Fields::int($data, 'id'),
            zone: Fields::string($data, 'zone'),
            subDomain: Fields::nullableString($data, 'subDomain'),
            type: RecordType::fromOvh(Fields::enumValue($data, 'fieldType')),
            target: Fields::string($data, 'target'),
            ttl: Fields::nullableInt($data, 'ttl'),
        );
    }
}

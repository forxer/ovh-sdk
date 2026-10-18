<?php

declare(strict_types=1);

namespace OvhSdk\Data\Dns;

use OvhSdk\Enums\RedirectionType;
use OvhSdk\Support\Fields;
use UnexpectedValueException;

/**
 * Schema domain.zone.Redirection. The title, description and keywords only apply to invisible redirections.
 */
final readonly class Redirection
{
    public function __construct(
        public int $id,
        public string $zone,
        public ?string $subDomain,
        public RedirectionType $type,
        public string $target,
        public ?string $title,
        public ?string $description,
        public ?string $keywords,
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
            type: RedirectionType::fromOvh(Fields::enumValue($data, 'type')),
            target: Fields::string($data, 'target'),
            title: Fields::nullableString($data, 'title'),
            description: Fields::nullableString($data, 'description'),
            keywords: Fields::nullableString($data, 'keywords'),
        );
    }
}

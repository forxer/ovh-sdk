<?php

declare(strict_types=1);

namespace OvhSdk\Data\Service;

use DateTimeImmutable;
use OvhSdk\Enums\RenewalType;
use OvhSdk\Enums\ServiceStatus;
use OvhSdk\Exceptions\CouldNotReadResponse;
use OvhSdk\Support\Dates;
use OvhSdk\Support\Fields;

/**
 * Schema services.Service. Contacts are not kept.
 */
final readonly class ServiceInfos
{
    /**
     * @param  list<int>|null  $possibleRenewPeriod
     */
    public function __construct(
        public string $domain,
        public DateTimeImmutable $expiration,
        public DateTimeImmutable $creation,
        public ?DateTimeImmutable $engagedUpTo,
        public ?RenewSettings $renew,
        public RenewalType $renewalType,
        public ServiceStatus $status,
        public int $serviceId,
        public bool $canDeleteAtExpiration,
        public ?array $possibleRenewPeriod,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     *
     * @throws CouldNotReadResponse
     */
    public static function fromArray(array $data): self
    {
        $renew = Fields::nullableObject($data, 'renew');

        return new self(
            domain: Fields::string($data, 'domain'),
            expiration: Dates::parse(Fields::string($data, 'expiration')),
            creation: Dates::parse(Fields::string($data, 'creation')),
            engagedUpTo: Dates::parseNullable(Fields::nullableString($data, 'engagedUpTo')),
            renew: $renew === null ? null : RenewSettings::fromArray($renew),
            renewalType: RenewalType::fromOvh(Fields::enumValue($data, 'renewalType')),
            status: ServiceStatus::fromOvh(Fields::enumValue($data, 'status')),
            serviceId: Fields::int($data, 'serviceId'),
            canDeleteAtExpiration: Fields::bool($data, 'canDeleteAtExpiration'),
            possibleRenewPeriod: Fields::nullableIntList($data, 'possibleRenewPeriod'),
        );
    }
}

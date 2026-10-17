<?php

declare(strict_types=1);

namespace OvhSdk\Data\Domain;

use DateTimeImmutable;
use OvhSdk\Enums\DomainState;
use OvhSdk\Enums\RenewalState;
use OvhSdk\Enums\SuspensionState;
use OvhSdk\Support\Dates;
use OvhSdk\Support\Fields;
use UnexpectedValueException;

/**
 * Schema domain.DomainService, reduced to the properties below: contacts, DNSSEC, glue records, offer, IAM metadata
 * and the other properties are ignored.
 */
final readonly class Domain
{
    /**
     * @param  list<NameServer>  $nameServers
     */
    public function __construct(
        public string $domain,
        public DateTimeImmutable $expirationDate,
        public DateTimeImmutable $renewalDate,
        public RenewalState $renewalState,
        public DomainState $state,
        public SuspensionState $suspensionState,
        public string $nameServerType,
        public array $nameServers,
        public string $whoisOwner,
        public DateTimeImmutable $lastUpdate,
        public int $serviceId,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     *
     * @throws UnexpectedValueException
     */
    public static function fromArray(array $data): self
    {
        return new self(
            domain: Fields::string($data, 'domain'),
            expirationDate: Dates::parse(Fields::string($data, 'expirationDate')),
            renewalDate: Dates::parse(Fields::string($data, 'renewalDate')),
            renewalState: RenewalState::fromOvh(Fields::enumValue($data, 'renewalState')),
            state: DomainState::fromOvh(Fields::enumValue($data, 'state')),
            suspensionState: SuspensionState::fromOvh(Fields::enumValue($data, 'suspensionState')),
            nameServerType: Fields::string($data, 'nameServerType'),
            nameServers: array_map(NameServer::fromArray(...), Fields::objectList($data, 'nameServers')),
            whoisOwner: Fields::string($data, 'whoisOwner'),
            lastUpdate: Dates::parse(Fields::string($data, 'lastUpdate')),
            serviceId: Fields::int($data, 'serviceId'),
        );
    }
}

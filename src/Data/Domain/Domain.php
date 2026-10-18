<?php

declare(strict_types=1);

namespace OvhSdk\Data\Domain;

use DateTimeImmutable;
use OvhSdk\Enums\DnssecState;
use OvhSdk\Enums\DomainOffer;
use OvhSdk\Enums\DomainState;
use OvhSdk\Enums\RenewalState;
use OvhSdk\Enums\SuspensionState;
use OvhSdk\Enums\TransferLockStatus;
use OvhSdk\Exceptions\CouldNotReadResponse;
use OvhSdk\Support\Dates;
use OvhSdk\Support\Fields;

/**
 * Schema domain.DomainService, reduced to the properties below: contacts, glue records, IAM metadata and the other
 * properties are ignored.
 *
 * The schema describes "dnssecSupported" as "Whether DNSSEC is activated for this domain name", which contradicts its
 * name; read $dnssecState to know whether DNSSEC is enabled.
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
        public TransferLockStatus $transferLockStatus,
        public DnssecState $dnssecState,
        public bool $dnssecSupported,
        public DomainOffer $offer,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     *
     * @throws CouldNotReadResponse
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
            transferLockStatus: TransferLockStatus::fromOvh(Fields::enumValue($data, 'transferLockStatus')),
            dnssecState: DnssecState::fromOvh(Fields::enumValue($data, 'dnssecState')),
            dnssecSupported: Fields::bool($data, 'dnssecSupported'),
            offer: DomainOffer::fromOvh(Fields::enumValue($data, 'offer')),
        );
    }
}

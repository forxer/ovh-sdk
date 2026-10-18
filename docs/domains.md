---
title: Domains
order: 3
---

Domains
=======

The domains of the account are reached through `$ovh->domain()`, which returns an `OvhSdk\Resources\DomainResource`.

Methods
-------

| Method | Returns | OVHcloud calls |
|---|---|---|
| `ids()` | `list<string>`: domain names | `GET /domain`, one call |
| `list()` | `list<OvhSdk\Data\Domain\Domain>` | `GET /domain`, then `GET /domain/{serviceName}` per domain |
| `get(string $domain)` | `OvhSdk\Data\Domain\Domain` | `GET /domain/{serviceName}` |
| `serviceInfos(string $domain)` | `OvhSdk\Data\Service\ServiceInfos` | `GET /domain/{serviceName}/serviceInfos` |
| `zones()` | `OvhSdk\Resources\ZonesResource` | none, see [DNS zones](./dns-zones.md) |
| `zone(string $zoneName)` | `OvhSdk\Resources\ZoneResource` | none, see [DNS zones](./dns-zones.md) |

```php
$names = $ovh->domain()->ids();                         // ['example.com', 'example.org']

$domain = $ovh->domain()->get('example.com');
$domain->expirationDate;                                // DateTimeImmutable
$domain->renewalState;                                  // OvhSdk\Enums\RenewalState
$domain->transferLockStatus;                            // OvhSdk\Enums\TransferLockStatus

$service = $ovh->domain()->serviceInfos('example.com');
$service->expiration;                                   // DateTimeImmutable
$service->renew?->automatic;                            // ?bool, renew may be null
```

ids() and list()
----------------

The list routes of the `/v1` API only return identifiers. The SDK follows the same convention for domains, DNS zones and DNS records:

- `ids()` makes a single call and returns the identifiers (names for domains);
- `list()` returns complete DTOs, at the cost of **one call per element** on top of the list call: 300 domains mean 301 calls.

`list()` sends the per element calls in parallel, through a Saloon pool, five at a time. It returns the DTOs in the order of the identifiers. When a call fails, `list()` waits for the others, then throws the exception of the first failing identifier in the list: it never returns a partial list.

When only a few elements are needed, prefer `ids()` followed by `get()` on the chosen ones. `Domain` already holds the expiration date (`expirationDate`) and the renewal state (`renewalState`), which tells whether the domain renews automatically; the renewal settings (`renew`) and the renewal type (`renewalType`) are in `ServiceInfos`, at the cost of one more `serviceInfos()` call per domain.

Domain
------

`OvhSdk\Data\Domain\Domain`, from the `domain.DomainService` schema, reduced to the properties below (contacts, glue records and IAM metadata are not kept).

| Property | Type | Content |
|---|---|---|
| `domain` | `string` | domain name |
| `expirationDate` | `DateTimeImmutable` | date the domain expires if it is not renewed |
| `renewalDate` | `DateTimeImmutable` | date the domain should be renewed (relevant when `renewalState` is `AutomaticRenew`) |
| `renewalState` | `OvhSdk\Enums\RenewalState` | renewal state |
| `state` | `OvhSdk\Enums\DomainState` | state of the domain |
| `suspensionState` | `OvhSdk\Enums\SuspensionState` | suspension state |
| `nameServerType` | `string` | raw value of the name server type, from the `domain.nameServer.NameServerTypeEnum` schema: `anycast`, `dedicated`, `empty`, `external`, `hold`, `hosted`, `hosting`, `mixed` or `parking` |
| `nameServers` | `list<OvhSdk\Data\Domain\NameServer>` | name servers |
| `whoisOwner` | `string` | contact id of the owner (registrant) |
| `lastUpdate` | `DateTimeImmutable` | last update |
| `serviceId` | `int` | service identifier |
| `transferLockStatus` | `OvhSdk\Enums\TransferLockStatus` | transfer lock status: `Locked` when the domain cannot be transferred to another registrar |
| `dnssecState` | `OvhSdk\Enums\DnssecState` | DNSSEC state of the domain |
| `dnssecSupported` | `bool` | DNSSEC flag of the domain, ambiguous: see below |
| `offer` | `OvhSdk\Enums\DomainOffer` | offer of the domain |

`dnssecSupported` is ambiguous: its name says DNSSEC is supported, but the official schema describes it as "Whether DNSSEC is activated for this domain name". The SDK exposes the value as OVHcloud sends it. The domains checked while writing the SDK had DNSSEC both supported and enabled, which does not settle the question. To know whether DNSSEC is enabled, read `dnssecState`.

`OvhSdk\Data\Domain\NameServer`:

| Property | Type |
|---|---|
| `nameServer` | `string` |
| `ipv4` | `?string` |
| `ipv6` | `?string` |

ServiceInfos
------------

`OvhSdk\Data\Service\ServiceInfos`, from the `services.Service` schema (contacts are not kept).

| Property | Type | Content |
|---|---|---|
| `domain` | `string` | domain name |
| `expiration` | `DateTimeImmutable` | expiration date of the service |
| `creation` | `DateTimeImmutable` | creation date of the service |
| `engagedUpTo` | `?DateTimeImmutable` | end of the commitment, if any |
| `renew` | `?OvhSdk\Data\Service\RenewSettings` | renewal settings, if any |
| `renewalType` | `OvhSdk\Enums\RenewalType` | renewal type; it does not tell whether the service renews automatically (see below) |
| `status` | `OvhSdk\Enums\ServiceStatus` | status of the service |
| `serviceId` | `int` | service identifier |
| `canDeleteAtExpiration` | `bool` | the service can be set up to be deleted at expiration |
| `possibleRenewPeriod` | `?list<int>` | possible renewal periods, in months |

`renewalType` does not tell whether the service renews automatically: a domain can have the `AutomaticV2016` renewal type with `renew->automatic` set to `false` and a `renewalState` of `ManualRenew`. To know whether a domain renews automatically, read `Domain::$renewalState` or `ServiceInfos::$renew->automatic`.

`OvhSdk\Data\Service\RenewSettings`, from the `service.RenewType` schema:

| Property | Type | Content |
|---|---|---|
| `automatic` | `bool` | the service is renewed automatically |
| `deleteAtExpiration` | `bool` | the service will be deleted at expiration |
| `forced` | `bool` | the service is forced to be renewed |
| `manualPayment` | `?bool` | the service needs to be renewed and paid manually |
| `period` | `?int` | renewal period, in months |

Dates
-----

Dates are `DateTimeImmutable`. The `datetime` values of the schema (`expirationDate`, `renewalDate`, `lastUpdate`) keep the offset sent by OVHcloud. The `date` values (`expiration`, `creation`, `engagedUpTo`) have no time: they are read at midnight, in the default timezone of the PHP process.

Enums
-----

Every enum read from OVHcloud has an `Unknown` case (value `'__unknown__'`): a value added by OVHcloud after this version of the SDK is read as `Unknown` instead of failing. `fromOvh(?string $value)` performs this resolution.

| Enum | Cases (OVHcloud value) |
|---|---|
| `OvhSdk\Enums\DomainState` | `AutorenewInProgress` (`autorenew_in_progress`), `AutorenewRegistryInProgress` (`autorenew_registry_in_progress`), `Deleted` (`deleted`), `Dispute` (`dispute`), `Expired` (`expired`), `Ok` (`ok`), `OutgoingTransfer` (`outgoing_transfer`), `PendingCreate` (`pending_create`), `PendingDelete` (`pending_delete`), `PendingIncomingTransfer` (`pending_incoming_transfer`), `PendingInstallation` (`pending_installation`), `RegistrySuspended` (`registry_suspended`), `Restorable` (`restorable`), `TechnicalSuspended` (`technical_suspended`), `Unknown` |
| `OvhSdk\Enums\RenewalState` | `AutomaticRenew` (`automatic_renew`), `CancellationComplete` (`cancellation_complete`), `CancellationRequested` (`cancellation_requested`), `ManualRenew` (`manual_renew`), `Unpaid` (`unpaid`), `Unknown` |
| `OvhSdk\Enums\SuspensionState` | `NotSuspended` (`not_suspended`), `Suspended` (`suspended`), `Unknown` |
| `OvhSdk\Enums\TransferLockStatus` | `Locked` (`locked`), `Locking` (`locking`), `Unavailable` (`unavailable`), `Unlocked` (`unlocked`), `Unlocking` (`unlocking`), `Unknown` |
| `OvhSdk\Enums\DnssecState` | `Disabled` (`disabled`), `Enabled` (`enabled`), `NotSupported` (`not_supported`), `Unknown` |
| `OvhSdk\Enums\DomainOffer` | `Diamond` (`diamond`), `Gold` (`gold`), `Platinum` (`platinum`), `Unknown` |
| `OvhSdk\Enums\RenewalType` | `AutomaticForcedProduct` (`automaticForcedProduct`), `AutomaticV2012` (`automaticV2012`), `AutomaticV2014` (`automaticV2014`), `AutomaticV2016` (`automaticV2016`), `Manual` (`manual`), `OneShot` (`oneShot`), `Option` (`option`), `Unknown` |
| `OvhSdk\Enums\ServiceStatus` | `AutorenewInProgress` (`autorenewInProgress`), `Expired` (`expired`), `InCreation` (`inCreation`), `Ok` (`ok`), `PendingDebt` (`pendingDebt`), `UnPaid` (`unPaid`), `Unknown` |

Example: domains expiring soon
------------------------------

```php
use OvhSdk\Enums\RenewalState;

$limit = new DateTimeImmutable('+30 days');

foreach ($ovh->domain()->list() as $domain) {
    if ($domain->expirationDate < $limit && $domain->renewalState !== RenewalState::AutomaticRenew) {
        echo $domain->domain.' expires on '.$domain->expirationDate->format('Y-m-d').PHP_EOL;
    }
}
```

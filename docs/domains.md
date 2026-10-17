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

When only a few elements are needed, prefer `ids()` followed by `get()` on the chosen ones. Knowing the expiration and the renewal mode of every domain costs `list()` plus one `serviceInfos()` per domain.

Domain
------

`OvhSdk\Data\Domain\Domain`, from the `domain.DomainService` schema, reduced to the properties below (contacts, DNSSEC, glue records, offer and IAM metadata are not kept).

| Property | Type | Content |
|---|---|---|
| `domain` | `string` | domain name |
| `expirationDate` | `DateTimeImmutable` | date the domain expires if it is not renewed |
| `renewalDate` | `DateTimeImmutable` | date the domain should be renewed (relevant when `renewalState` is `AutomaticRenew`) |
| `renewalState` | `OvhSdk\Enums\RenewalState` | renewal state |
| `state` | `OvhSdk\Enums\DomainState` | state of the domain |
| `suspensionState` | `OvhSdk\Enums\SuspensionState` | suspension state |
| `nameServerType` | `string` | raw value of the name server type (`hosted`, `external`...) |
| `nameServers` | `list<OvhSdk\Data\Domain\NameServer>` | name servers |
| `whoisOwner` | `string` | contact id of the owner (registrant) |
| `lastUpdate` | `DateTimeImmutable` | last update |
| `serviceId` | `int` | service identifier |

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
| `renewalType` | `OvhSdk\Enums\RenewalType` | renewal type |
| `status` | `OvhSdk\Enums\ServiceStatus` | status of the service |
| `serviceId` | `int` | service identifier |
| `canDeleteAtExpiration` | `bool` | the service can be set up to be deleted at expiration |
| `possibleRenewPeriod` | `?list<int>` | possible renewal periods, in months |

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

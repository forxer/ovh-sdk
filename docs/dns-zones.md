---
title: DNS zones
order: 4
---

DNS zones
=========

DNS zones are reached through the domain resource:

- `$ovh->domain()->zones()` returns an `OvhSdk\Resources\ZonesResource`, for every zone of the account;
- `$ovh->domain()->zone('example.com')` returns an `OvhSdk\Resources\ZoneResource`, for one zone, and gives access to its records.

Zones
-----

| Method | Returns | OVHcloud calls |
|---|---|---|
| `zones()->ids()` | `list<string>`: zone names | `GET /domain/zone`, one call |
| `zones()->list()` | `list<OvhSdk\Data\Dns\Zone>` | `GET /domain/zone`, then `GET /domain/zone/{zoneName}` per zone |
| `zone($zoneName)->get()` | `OvhSdk\Data\Dns\Zone` | `GET /domain/zone/{zoneName}` |
| `zone($zoneName)->refresh()` | `void` | `POST /domain/zone/{zoneName}/refresh` |
| `zone($zoneName)->records()` | `OvhSdk\Resources\RecordsResource` | none |

`zones()->list()` follows the `ids()` / `list()` convention described in [Domains](./domains.md): one call per zone on top of the list call, sent in parallel five at a time, results in the order of the names, and an exception instead of a partial list when one call fails.

`ZoneResource` and `RecordsResource` expose the zone name in their `zoneName` property.

### Zone

`OvhSdk\Data\Dns\Zone`, from the `domain.Zone` schema (the IAM metadata is not kept).

| Property | Type | Content |
|---|---|---|
| `name` | `string` | zone name |
| `nameServers` | `list<string>` | name servers hosting the zone |
| `lastUpdate` | `?DateTimeImmutable` | last update of the zone |
| `dnssecSupported` | `bool` | DNSSEC is supported by the zone |
| `dnssecActivated` | `bool` | DNSSEC is activated for the zone |
| `hasDnsAnycast` | `bool` | the zone has a DNS Anycast subscription |

Records
-------

```php
$records = $ovh->domain()->zone('example.com')->records();
```

| Method | Returns | OVHcloud calls |
|---|---|---|
| `ids(?RecordType $type = null, ?string $subDomain = null)` | `list<int>`: record ids | `GET /domain/zone/{zoneName}/record`, one call |
| `list(?RecordType $type = null, ?string $subDomain = null)` | `list<OvhSdk\Data\Dns\Record>` | the call of `ids()`, then `GET /domain/zone/{zoneName}/record/{id}` per record |
| `get(int $id)` | `OvhSdk\Data\Dns\Record` | `GET /domain/zone/{zoneName}/record/{id}` |
| `create(RecordType $type, string $target, ?string $subDomain = null, ?int $ttl = null)` | `OvhSdk\Data\Dns\Record` | `POST /domain/zone/{zoneName}/record` |
| `update(int $id, ?string $target = null, ?string $subDomain = null, ?int $ttl = null)` | `void` | `PUT /domain/zone/{zoneName}/record/{id}` |
| `delete(int $id)` | `void` | `DELETE /domain/zone/{zoneName}/record/{id}` |

`RecordType` is `OvhSdk\Enums\RecordType`.

### Listing

```php
use OvhSdk\Enums\RecordType;

$ids = $records->ids();                                         // every record
$ids = $records->ids(type: RecordType::CNAME);                  // CNAME records only
$wwwRecords = $records->list(type: RecordType::A, subDomain: 'www');
```

The filters are sent as the `fieldType` and `subDomain` query parameters; the official schema describes them as `like` and `ilike` (case insensitive) filters. `list()` follows the `ids()` / `list()` convention: one call per record on top of the list call, five at a time.

### Creating

```php
$record = $records->create(
    type: RecordType::A,
    target: '203.0.113.10',
    subDomain: 'app',
    ttl: 3600,
);

$record->id;   // int, to update or delete the record later
```

A `null` sub-domain or TTL is not sent: without a sub-domain, the record is created at the zone root.

### Updating

```php
$records->update($record->id, target: '203.0.113.20');
$records->update($record->id, subDomain: '');   // moves the record to the zone root
```

Only the arguments that are not `null` are sent, so `null` means "unchanged". An empty string is sent as is: `subDomain: ''` targets the zone root. A call without any field throws an `InvalidArgumentException` before anything is sent. As `null` means "unchanged", `update()` cannot reset a TTL to `null`.

### Deleting

```php
$records->delete($record->id);
```

### Record

`OvhSdk\Data\Dns\Record`, from the `domain.zone.Record` schema. The `fieldType` field of OVHcloud is exposed as `$type`.

| Property | Type | Content |
|---|---|---|
| `id` | `int` | record id |
| `zone` | `string` | zone name |
| `subDomain` | `?string` | sub-domain, relative to the zone; nullable as in the official schema, so check for both `null` and `''` when looking for root records |
| `type` | `OvhSdk\Enums\RecordType` | record type |
| `target` | `string` | target of the record |
| `ttl` | `?int` | TTL, in seconds, if one is set |

### RecordType

`OvhSdk\Enums\RecordType` has one case per type of the `domain.zone.RecordTypeEnum` schema, named after the value: `A`, `AAAA`, `CAA`, `CNAME`, `DKIM`, `DMARC`, `DNAME`, `HTTPS`, `LOC`, `MX`, `NAPTR`, `NS`, `PTR`, `RP`, `SPF`, `SRV`, `SSHFP`, `SVCB`, `TLSA`, `TXT`, and `Unknown` (value `'__unknown__'`).

A record of a type added by OVHcloud after this version of the SDK is read with the type `RecordType::Unknown`. `RecordType::Unknown` cannot be sent: `ids()`, `list()` and `create()` throw an `InvalidArgumentException` when they receive it.

Refreshing the zone
-------------------

Creating, updating or deleting a record does not publish it: OVHcloud serves the changes once the zone is refreshed. The SDK never refreshes on its own, so that several changes can be grouped and published with a single call:

```php
use OvhSdk\Enums\RecordType;

$zone = $ovh->domain()->zone('example.com');

$zone->records()->create(RecordType::A, '203.0.113.10', 'app');
$zone->records()->create(RecordType::CNAME, 'app.example.com.', 'www.app');

$zone->refresh();
```

`refresh()` returns nothing: OVHcloud answers with no content, and an error raises an exception (see [Errors](./errors.md)).

---
title: DNS zones
order: 4
---

DNS zones
=========

DNS zones are reached through the domain resource:

- `$ovh->domain()->zones()` returns an `OvhSdk\Resources\ZonesResource`, for every zone of the account;
- `$ovh->domain()->zone('example.com')` returns an `OvhSdk\Resources\ZoneResource`, for one zone, and gives access to its records, web redirections and tasks.

Zones
-----

| Method | Returns | OVHcloud calls |
|---|---|---|
| `zones()->ids()` | `list<string>`: zone names | `GET /domain/zone`, one call |
| `zones()->list()` | `list<OvhSdk\Data\Dns\Zone>` | `GET /domain/zone`, then `GET /domain/zone/{zoneName}` per zone |
| `zone($zoneName)->get()` | `OvhSdk\Data\Dns\Zone` | `GET /domain/zone/{zoneName}` |
| `zone($zoneName)->status()` | `OvhSdk\Data\Dns\ZoneStatus` | `GET /domain/zone/{zoneName}/status` |
| `zone($zoneName)->export()` | `string`: the zone file, in BIND format | `GET /domain/zone/{zoneName}/export` |
| `zone($zoneName)->exportedRecords()` | `list<OvhSdk\Data\Dns\ZoneFileRecord>`: the records of the zone file | `GET /domain/zone/{zoneName}/export` |
| `zone($zoneName)->history(?DateTimeInterface $from = null, ?DateTimeInterface $to = null)` | `list<DateTimeImmutable>`: creation dates of the restore points, oldest first | `GET /domain/zone/{zoneName}/history` |
| `zone($zoneName)->refresh()` | `void` | `POST /domain/zone/{zoneName}/refresh` |
| `zone($zoneName)->records()` | `OvhSdk\Resources\RecordsResource` | none |
| `zone($zoneName)->redirections()` | `OvhSdk\Resources\RedirectionsResource` | none |
| `zone($zoneName)->tasks()` | `OvhSdk\Resources\ZoneTasksResource` | none |

`zones()->list()` follows the `ids()` / `list()` convention described in [Domains](./domains.md): one call per zone on top of the list call, sent in parallel five at a time, results in the order of the names, and an exception instead of a partial list when one call fails.

`ZoneResource`, `RecordsResource`, `RedirectionsResource` and `ZoneTasksResource` expose the zone name in their `zoneName` property.

### Zone

`OvhSdk\Data\Dns\Zone`, from the `domain.Zone` schema (the IAM metadata is not kept).

| Property | Type | Content |
|---|---|---|
| `name` | `string` | zone name |
| `nameServers` | `list<string>` | name servers OVHcloud lists for the zone; the list can hold a server that is not authoritative for the zone, so query the `NS` records when you need the servers actually serving it |
| `lastUpdate` | `?DateTimeImmutable` | last update of the zone |
| `dnssecSupported` | `bool` | DNSSEC is supported by the zone |
| `dnssecActivated` | `bool` | DNSSEC is activated for the zone |
| `hasDnsAnycast` | `bool` | the zone has a DNS Anycast subscription |

### ZoneStatus

`OvhSdk\Data\Dns\ZoneStatus`, from the `domain.zone.Status` schema, plus `lastRefresh`, which OVHcloud sends but the schema does not declare.

```php
$status = $ovh->domain()->zone('example.com')->status();

$status->isDeployed;                    // bool
$status->errors;                        // ?list<string>, [] when the zone has none
$status->lastRefresh;                   // ?DateTimeImmutable
```

| Property | Type | Content |
|---|---|---|
| `isDeployed` | `bool` | the zone is deployed |
| `errors` | `?list<string>` | errors of the zone; OVHcloud sends an empty list when there is none, `null` is kept as in the official schema |
| `warnings` | `?list<string>` | warnings of the zone, as `errors` |
| `lastRefresh` | `?DateTimeImmutable` | last refresh of the zone, with its microseconds and the offset sent by OVHcloud; `null` when the response does not hold it, as the schema does not guarantee it |

Zone file
---------

```php
$zoneFile = $ovh->domain()->zone('example.com')->export();   // string
```

`export()` returns the whole zone as a BIND zone file, as OVHcloud generates it, in a single call whatever the number of records.

### Records of the zone file

To read every record of a zone, splitting its export is much cheaper than `records()->list()`, which makes one call per record: a zone of 170 records costs one call instead of 171.

```php
foreach ($ovh->domain()->zone('example.com')->exportedRecords() as $record) {
    $record->subDomain;   // string, '' at the zone root
    $record->ttl;         // ?int, null when the record follows the default TTL of the zone
    $record->type;        // OvhSdk\Enums\RecordType
    $record->target;      // string, as written in the file
}
```

`exportedRecords()` returns the records only. To read the default TTL of the zone too, split the export with `OvhSdk\Data\Dns\ZoneFile::parse()`, which takes any zone file and the name of its zone:

```php
use OvhSdk\Data\Dns\ZoneFile;

$zone = $ovh->domain()->zone('example.com');
$zoneFile = ZoneFile::parse($zone->export(), $zone->zoneName);

$zoneFile->defaultTtl;   // ?int, from the $TTL directive
$zoneFile->records;      // list<ZoneFileRecord>, as exportedRecords() returns them
```

### ZoneFile

`OvhSdk\Data\Dns\ZoneFile`, built by `ZoneFile::parse(string $content, string $zoneName)`.

| Property | Type | Content |
|---|---|---|
| `defaultTtl` | `?int` | default TTL of the zone, in seconds, from the `$TTL` directive; `null` when the file has none |
| `records` | `list<OvhSdk\Data\Dns\ZoneFileRecord>` | the records, in the order of the file. The SOA record is left out, as the record API does not hold it |

### ZoneFileRecord

`OvhSdk\Data\Dns\ZoneFileRecord`. A zone file holds no record id: to update or delete one of these records, find its id with `records()->ids(type: ..., subDomain: ...)`.

| Property | Type | Content |
|---|---|---|
| `subDomain` | `string` | name relative to the zone, as `Record::$subDomain`: `''` at the zone root, `www` for `www.example.com.`. Kept in the case of the file |
| `ttl` | `?int` | TTL of the record, in seconds; `null` when its line has none and the default TTL of the zone applies. The record API reads such a record with a TTL of `0` |
| `type` | `OvhSdk\Enums\RecordType` | type written in the file; `RecordType::Unknown` for a type the enum does not have |
| `target` | `string` | data of the record as written in the file, its fields joined by a single space: `1 mx1.mail.ovh.net.` for an MX (priority included), a TXT between double quotes (`"v=spf1 include:mx.ovh.com -all"`), as `Record::$target`. A TXT split into several strings, such as a long DKIM key, keeps them: `"v=DKIM1; k=rsa; " "p=MIIB..."`. Escapes are kept, and the names are not completed: a relative `www` stays `www` |

### Comparing with the record API

The zone file and the record API hold the same records, the SOA record aside: `exportedRecords()` returns as many records as `records()->ids()`.

Their types can differ. The `DKIM`, `DMARC` and `SPF` types are forms of the OVHcloud Control Panel: in the DNS zone, these records are TXT records, as the OVHcloud guides on [DKIM](https://docs.ovhcloud.com/en/guides/web-cloud/domains/dns-zone-dkim.md), [DMARC](https://docs.ovhcloud.com/en/guides/web-cloud/domains/dns-zone-dmarc.md) and [SPF](https://docs.ovhcloud.com/en/guides/web-cloud/domains/dns-zone-spf.md) explain. A record that `records()` reads as `RecordType::DKIM`, `RecordType::DMARC` or `RecordType::SPF` is a `RecordType::TXT` in the zone file: compare the TXT records of the zone file with the records of these four types.

### Supported syntax

`ZoneFile::parse()` reads the zone file format of RFC 1035 (section 5.1), the name of the zone being the first origin:

- the `$TTL` and `$ORIGIN` directives; `$INCLUDE` and the other directives are refused;
- comments, from a `;` outside double quotes to the end of the line;
- an owner left blank, which repeats the owner of the previous record (OVHcloud writes the records of a name this way), and `@` for the origin;
- absolute names, ending with a dot, and relative names, completed by the origin; the zone name is compared case insensitively;
- parentheses spreading a record over several lines;
- the TTL, in seconds or with units (`1h30m`), and the class (`IN`), both optional and in either order;
- quoted strings, escaped double quotes (`\"`) included, and several strings in one record.

A file it cannot read throws an `UnexpectedValueException` naming the line: an unterminated quoted string, unbalanced parentheses, a record without type or data, a name outside the zone, a directive it does not support, or a second `$TTL` changing the default TTL (the records without a TTL of their own would then follow different defaults). An empty zone name throws an `InvalidArgumentException`.

History
-------

`history()` returns the creation dates of the restore points OVHcloud keeps for the zone, in a single call:

```php
$zone = $ovh->domain()->zone('example.com');

$history = $zone->history();                                          // every restore point
$history = $zone->history(from: new DateTimeImmutable('-1 month'));   // the restore points of the last month

$latest = $history === [] ? null : $history[array_key_last($history)];
```

- The dates are sorted oldest first, as OVHcloud does not guarantee any order; `array_reverse()` puts the most recent first.
- They keep the microseconds and the offset OVHcloud sends, for example `2026-10-08T09:28:33.070330+02:00`.
- `from` and `to` accept any `DateTimeInterface`, and are sent as the `creationDate.from` and `creationDate.to` query parameters, with their microseconds and offset.

The SDK reads these dates only: the content of a restore point (`GET /domain/zone/{zoneName}/history/{creationDate}`) and the restoration are not covered.

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

A `null` sub-domain or TTL is not sent: without a sub-domain, the record is created at the zone root; without a TTL, OVHcloud stores a TTL of `0` and the default TTL of the zone applies.

### Updating

```php
$records->update($record->id, target: '203.0.113.20');
$records->update($record->id, subDomain: '');   // moves the record to the zone root
```

Only the arguments that are not `null` are sent, so `null` means "unchanged". An empty string is sent as is: `subDomain: ''` targets the zone root. A call without any field throws an `InvalidArgumentException` before anything is sent. As `null` means "unchanged", `update()` never sends a `null` TTL.

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
| `target` | `string` | target of the record, as OVHcloud stores it: a `TXT` target is read back between double quotes (`"v=spf1 -all"`), even when it was created without them |
| `ttl` | `?int` | TTL, in seconds; `0`, not `null`, when the record has no TTL of its own and follows the default TTL of the zone. Nullable as in the official schema |

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

Web redirections
----------------

```php
$redirections = $ovh->domain()->zone('example.com')->redirections();
```

| Method | Returns | OVHcloud calls |
|---|---|---|
| `ids(?string $subDomain = null)` | `list<int>`: redirection ids | `GET /domain/zone/{zoneName}/redirection`, one call |
| `list(?string $subDomain = null)` | `list<OvhSdk\Data\Dns\Redirection>` | the call of `ids()`, then `GET /domain/zone/{zoneName}/redirection/{id}` per redirection |
| `get(int $id)` | `OvhSdk\Data\Dns\Redirection` | `GET /domain/zone/{zoneName}/redirection/{id}` |

The filter is sent as the `subDomain` query parameter. `list()` follows the `ids()` / `list()` convention: one call per redirection on top of the list call, five at a time. The SDK reads the redirections only: creating, updating or deleting one is not covered.

### Redirection

`OvhSdk\Data\Dns\Redirection`, from the `domain.zone.Redirection` schema.

| Property | Type | Content |
|---|---|---|
| `id` | `int` | redirection id |
| `zone` | `string` | zone name |
| `subDomain` | `?string` | sub-domain redirected, relative to the zone; nullable as in the official schema, so check for both `null` and `''` when looking for the zone root |
| `type` | `OvhSdk\Enums\RedirectionType` | how the browser is redirected |
| `target` | `string` | address the redirection leads to |
| `title` | `?string` | title of the page, for an invisible redirection |
| `description` | `?string` | description of the page, for an invisible redirection |
| `keywords` | `?string` | keywords of the page, for an invisible redirection |

### RedirectionType

`OvhSdk\Enums\RedirectionType`, from the `domain.zone.RedirectionTypeEnum` schema:

| Case | Value | Redirection |
|---|---|---|
| `Visible` | `visible` | HTTP 302 response |
| `VisiblePermanent` | `visiblePermanent` | HTTP 301 response |
| `Invisible` | `invisible` | HTML frame: the browser keeps showing the address of the sub-domain |
| `Unknown` | `__unknown__` | a type added by OVHcloud after this version of the SDK |

Tasks
-----

OVHcloud applies some changes of a zone through tasks: a refresh, a restoration, an import, a DNSSEC change...

```php
$tasks = $ovh->domain()->zone('example.com')->tasks();
```

| Method | Returns | OVHcloud calls |
|---|---|---|
| `ids(?TaskFunction $function = null, ?TaskStatus $status = null)` | `list<int>`: task ids | `GET /domain/zone/{zoneName}/task`, one call |
| `list(?TaskFunction $function = null, ?TaskStatus $status = null)` | `list<OvhSdk\Data\Dns\ZoneTask>` | the call of `ids()`, then `GET /domain/zone/{zoneName}/task/{id}` per task |
| `get(int $id)` | `OvhSdk\Data\Dns\ZoneTask` | `GET /domain/zone/{zoneName}/task/{id}` |

`TaskFunction` and `TaskStatus` are `OvhSdk\Enums\TaskFunction` and `OvhSdk\Enums\TaskStatus`. The filters are sent as the `function` and `status` query parameters, one value each:

```php
use OvhSdk\Enums\TaskStatus;

$running = [
    ...$tasks->list(status: TaskStatus::Todo),
    ...$tasks->list(status: TaskStatus::Doing),
];
```

`TaskFunction::Unknown` and `TaskStatus::Unknown` cannot be sent: `ids()` and `list()` throw an `InvalidArgumentException` when they receive one. The SDK reads the tasks only: accelerating, cancelling or relaunching one is not covered, the `canAccelerate`, `canCancel` and `canRelaunch` flags telling what OVHcloud would allow.

### ZoneTask

`OvhSdk\Data\Dns\ZoneTask`, from the `domain.zone.Task` schema.

| Property | Type | Content |
|---|---|---|
| `id` | `int` | task id |
| `function` | `OvhSdk\Enums\TaskFunction` | operation of the task |
| `status` | `OvhSdk\Enums\TaskStatus` | status of the task |
| `comment` | `?string` | comment of OVHcloud on the task |
| `creationDate` | `DateTimeImmutable` | creation of the task |
| `todoDate` | `DateTimeImmutable` | date the task is planned for |
| `doneDate` | `?DateTimeImmutable` | end of the task, `null` while it is not done |
| `lastUpdate` | `?DateTimeImmutable` | last update of the task |
| `canAccelerate` | `bool` | the task can be accelerated |
| `canCancel` | `bool` | the task can be cancelled |
| `canRelaunch` | `bool` | the task can be relaunched |

### TaskFunction and TaskStatus

| Enum | Cases (value) |
|---|---|
| `OvhSdk\Enums\TaskFunction` (`domain.TaskFunctionEnum`) | one case per value, named after it: `DnsAnycastActivate`, `DnsAnycastDeactivate`, `DnssecDisable`, `DnssecEnable`, `DnssecResigning`, `DnssecRollKsk`, `DnssecRollZsk`, `ZoneCheck`, `ZoneCreate`, `ZoneCut`, `ZoneDelete`, `ZoneImport`, `ZoneRefresh`, `ZoneRestore`, `Unknown` |
| `OvhSdk\Enums\TaskStatus` (`domain.TaskStatusEnum`) | `Cancelled` (`cancelled`), `Doing` (`doing`), `Done` (`done`), `Error` (`error`), `Problem` (`problem`), `Todo` (`todo`), `Unknown` |

A value added by OVHcloud after this version of the SDK is read as the `Unknown` case.

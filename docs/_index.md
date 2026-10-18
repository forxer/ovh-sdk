---
title: OVH SDK
order: 0
---

OVH SDK
=======

An unofficial, modern PHP SDK for the [OVHcloud API](https://api.ovh.com/), built on top of [Saloon](https://docs.saloon.dev/), with a built-in Laravel integration. It returns immutable DTOs and typed enums, never associative arrays.

> The package is in `0.x`: its public API may still change between minor versions.

Features
--------

- Saloon connector for the EU, CA and US regions of the OVHcloud API
- Two authentication modes: application keys (signed requests, server clock synchronisation) and OAuth2 service accounts
- Consumer key requests, from PHP or from an artisan command
- Domains: names, details (including transfer lock, DNSSEC and offer) and service information (expiration, renewal)
- DNS zones and records: read, create, update, delete and refresh, zone status and zone file export
- Typed exceptions for every OVHcloud error, with the OVH error class and query id
- Generic access to any endpoint not covered yet, signature included
- Framework agnostic core, optional Laravel 13 integration (configuration, facade, injection, cache, fake)
- Testable without any real API call, with Saloon's `MockClient`

Requirements
------------

- PHP 8.4 or higher
- Laravel 13, only for the Laravel integration

Quick start
-----------

```bash
composer require forxer/ovh-sdk
```

```php
use OvhSdk\Auth\ApplicationKeys;
use OvhSdk\Enums\Region;
use OvhSdk\Ovh;

$ovh = new Ovh(
    region: Region::Eu,
    credentials: new ApplicationKeys('application-key', 'application-secret', 'consumer-key'),
);

$domain = $ovh->domain()->get('example.com');

echo $domain->expirationDate->format('Y-m-d');
```

At a glance
-----------

Every call starts from the connector, `OvhSdk\Ovh`:

```php
use OvhSdk\Enums\RecordType;

$ovh->domain()->ids();                                   // list<string>, one call
$ovh->domain()->list();                                  // list<Domain>, the list call plus one per domain
$ovh->domain()->get('example.com');                      // Domain
$ovh->domain()->serviceInfos('example.com');             // ServiceInfos

$ovh->domain()->zones()->ids();                          // list<string>, one call
$ovh->domain()->zones()->list();                         // list<Zone>, the list call plus one per zone

$zone = $ovh->domain()->zone('example.com');
$zone->get();                                            // Zone
$zone->status();                                         // ZoneStatus
$zone->export();                                         // string, the BIND zone file in one call
$zone->refresh();                                        // applies the pending changes

$zone->records()->ids();                                 // list<int>, one call
$zone->records()->list();                                // list<Record>, the list call plus one per record
$zone->records()->get(42);                               // Record
$zone->records()->create(RecordType::A, '203.0.113.10', 'app');
$zone->records()->update(42, target: '203.0.113.20');
$zone->records()->delete(42);
```

### Calling an endpoint not covered yet

The connector offers `get()`, `post()`, `put()` and `delete()` on any path. They return the raw `Saloon\Http\Response`, and go through the same authentication, signature and typed exceptions as the resources:

```php
$me = $ovh->get('/me')->json();

$ovh->get('/domain/zone/example.com/record', ['fieldType' => 'MX']);
$ovh->post('/domain/zone/example.com/refresh');
$ovh->put('/domain/zone/example.com/record/42', ['target' => '203.0.113.20']);
$ovh->delete('/domain/zone/example.com/record/42');
```

A path is read in the `/v1` branch: `/me` and `/v1/me` both call `/v1/me`. A path starting with `/v2/` calls the `/v2` branch. `post()` and `put()` send their array as a JSON body, or no body at all when it is empty.

Saloon's `json()` only decodes a JSON object or array. Some routes answer with a JSON scalar: `GET /auth/time` sends an integer, `GET /domain/zone/{zoneName}/export` a string (between double quotes, with escaped line breaks; `zone($zoneName)->export()` decodes it for you). On such a response, `json()` throws a `TypeError`, for example `Cannot assign string to property Saloon\Http\Response::$decodedJson of type array`. Decode the body yourself:

```php
$serverTime = json_decode($ovh->get('/auth/time')->body(), true, 512, JSON_THROW_ON_ERROR);   // int
```

Contents
--------

- [Installation](./installation.md): requirements, Composer, connector, regions, cache and clock
- [Authentication](./authentication.md): application keys, consumer keys, service accounts, IAM actions
- [Domains](./domains.md): domain names, details and service information
- [DNS zones](./dns-zones.md): zones and records
- [Errors](./errors.md): exceptions by HTTP status and other failures
- [Laravel](./laravel.md): configuration, facade, injection, cache and artisan command
- [Testing](./testing.md): `MockClient` and `Ovh::fake()`
- [Changelog](../CHANGELOG.md)
- [Upgrade guide](../UPGRADE.md)

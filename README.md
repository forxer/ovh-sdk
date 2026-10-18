[![Latest Stable Version](https://poser.pugx.org/forxer/ovh-sdk/v)](https://packagist.org/packages/forxer/ovh-sdk)
[![Total Downloads](https://poser.pugx.org/forxer/ovh-sdk/downloads)](https://packagist.org/packages/forxer/ovh-sdk)
[![License](https://poser.pugx.org/forxer/ovh-sdk/license)](https://packagist.org/packages/forxer/ovh-sdk)
[![Tests](https://github.com/forxer/ovh-sdk/actions/workflows/tests.yml/badge.svg)](https://github.com/forxer/ovh-sdk/actions/workflows/tests.yml)

OVH SDK
=======

An unofficial, modern PHP SDK for the [OVHcloud API](https://api.ovh.com/), built on top of [Saloon](https://docs.saloon.dev/), with built-in Laravel integration.

> **0.x:** the public API may still change between minor versions until 1.0.

```php
use OvhSdk\Auth\ApplicationKeys;
use OvhSdk\Enums\RecordType;
use OvhSdk\Enums\Region;
use OvhSdk\Ovh;

$ovh = new Ovh(
    region: Region::Eu,
    credentials: new ApplicationKeys('application-key', 'application-secret', 'consumer-key'),
);

// Read every domain of the account
foreach ($ovh->domain()->list() as $domain) {
    echo $domain->domain.' expires on '.$domain->expirationDate->format('Y-m-d').PHP_EOL;
}

// Create a DNS record, then publish the zone
$zone = $ovh->domain()->zone('example.com');
$zone->records()->create(type: RecordType::A, target: '203.0.113.10', subDomain: 'app', ttl: 3600);
$zone->refresh();
```

Features
--------

* Fluent, typed resources returning immutable DTOs and enums
* Application keys (signed requests) and OAuth2 service accounts
* Domains, DNS zones and DNS records
* Typed exceptions for every OVHcloud error
* Generic access to any endpoint not covered yet
* Framework agnostic core, with an optional Laravel 13 integration in the same package
* Easy testing with Saloon's mocking tools

Requirements
------------

* PHP 8.4 or higher
* Laravel 13, only for the Laravel integration

Installation
------------

```bash
composer require forxer/ovh-sdk
```

Documentation
-------------

* **[Overview](./docs/_index.md)**
* **[Installation](./docs/installation.md)**: requirements, connector, regions, cache and clock
* **[Authentication](./docs/authentication.md)**: application keys, consumer keys, service accounts, IAM actions
* **[Domains](./docs/domains.md)** and **[DNS zones](./docs/dns-zones.md)**
* **[Errors](./docs/errors.md)**
* **[Laravel](./docs/laravel.md)**: configuration, facade, injection, cache and artisan command
* **[Testing](./docs/testing.md)**: `MockClient` and `Ovh::fake()`
* **[Changelog](./CHANGELOG.md)** and **[Upgrade guide](./UPGRADE.md)**

License
-------

This package is open-sourced software licensed under the [MIT license](./LICENSE).

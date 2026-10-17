---
title: Installation
order: 1
---

Installation
============

Requirements
------------

- PHP 8.4 or higher
- Laravel 13, only for the Laravel integration: the package declares a conflict with `laravel/framework` below 13.0

Composer installs the other dependencies: `saloonphp/saloon` 4.5 or higher, `psr/clock` and `psr/simple-cache`. The core uses no `illuminate/*` component: it works in any PHP project.

Installing with Composer
------------------------

```bash
composer require forxer/ovh-sdk
```

In a Laravel application, the service provider and the `Ovh` facade are discovered automatically: see [Laravel](./laravel.md) for the configuration.

Without Laravel
---------------

Create the connector yourself, with a region and credentials:

```php
use OvhSdk\Auth\ApplicationKeys;
use OvhSdk\Enums\Region;
use OvhSdk\Ovh;

$ovh = new Ovh(
    region: Region::Eu,
    credentials: new ApplicationKeys('application-key', 'application-secret', 'consumer-key'),
);
```

The constructor of `OvhSdk\Ovh` takes:

| Parameter | Type | Default | Role |
|---|---|---|---|
| `region` | `OvhSdk\Enums\Region` | required | API host, and OAuth2 token endpoint for service accounts |
| `credentials` | `Saloon\Contracts\Authenticator` | required | `OvhSdk\Auth\ApplicationKeys` or `OvhSdk\Auth\ServiceAccount`, see [Authentication](./authentication.md) |
| `cache` | `?Psr\SimpleCache\CacheInterface` | `null` | PSR-16 cache for the server time delta and the OAuth2 token |
| `clock` | `Psr\Clock\ClockInterface` | `OvhSdk\Support\SystemClock` | PSR-20 clock used for signed timestamps and token expiry |

The connector is a Saloon `Connector`: Saloon's own features (mock client, middleware, plugins) are available on it.

### Regions

| Case | API host |
|---|---|
| `Region::Eu` | `https://api.eu.ovhcloud.com` |
| `Region::Ca` | `https://api.ca.ovhcloud.com` |
| `Region::Us` | `https://api.us.ovhcloud.com` |

On a region, `apiUrl()` gives the API host and `oauth2TokenUrl()` the OAuth2 token endpoint (`Region::Eu->apiUrl()` for example) (see [Authentication](./authentication.md)). The resources of the SDK target the `/v1` branch of the API; the generic calls can also reach `/v2` (see the [overview](./_index.md)).

### Cache

Without a cache, the server time delta and the OAuth2 token live as long as the connector instance. With a PSR-16 cache, they are shared between instances and processes, which saves a call to OVHcloud on each new process:

```php
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

$ovh = new Ovh(
    region: Region::Eu,
    credentials: new ApplicationKeys('application-key', 'application-secret', 'consumer-key'),
    cache: new Psr16Cache(new FilesystemAdapter()),
);
```

Any PSR-16 implementation works: the example uses `symfony/cache`, which you would install yourself.

### Clock

The connector reads the time from a PSR-20 clock, `OvhSdk\Support\SystemClock` by default. Passing your own clock is mostly useful in tests, to freeze the time.

### Several accounts

The connector holds one set of credentials. To work with several OVHcloud accounts, create one connector per account.

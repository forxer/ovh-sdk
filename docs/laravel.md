---
title: Laravel
order: 6
---

Laravel
=======

The package ships its Laravel integration: a service provider, a facade, a configuration file, an artisan command and a test fake. It requires Laravel 13; the core of the SDK does not depend on Laravel.

Installation
------------

```bash
composer require forxer/ovh-sdk
```

Laravel discovers the service provider, `OvhSdk\Laravel\OvhSdkServiceProvider`, and the `Ovh` facade alias, `OvhSdk\Laravel\Facades\Ovh`, automatically.

Configuration
-------------

The configuration works without publishing it: the provider merges its defaults, and everything comes from the `.env` file. To publish `config/ovh-sdk.php`:

```bash
php artisan vendor:publish --tag=ovh-sdk-config
```

| Variable | Configuration key | Default | Role |
|---|---|---|---|
| `OVH_REGION` | `ovh-sdk.region` | `eu` | API region: `eu`, `ca` or `us` |
| `OVH_AUTH` | `ovh-sdk.auth` | `application_keys` | authentication mode: `application_keys` or `service_account` |
| `OVH_APPLICATION_KEY` | `ovh-sdk.application_keys.application_key` | none | application key, required in `application_keys` mode |
| `OVH_APPLICATION_SECRET` | `ovh-sdk.application_keys.application_secret` | none | application secret, required in `application_keys` mode |
| `OVH_CONSUMER_KEY` | `ovh-sdk.application_keys.consumer_key` | none | consumer key; may stay empty until it has been requested with `ovh:consumer-key` and validated |
| `OVH_CLIENT_ID` | `ovh-sdk.service_account.client_id` | none | service account client id, required in `service_account` mode |
| `OVH_CLIENT_SECRET` | `ovh-sdk.service_account.client_secret` | none | service account client secret, required in `service_account` mode |
| `OVH_CACHE_STORE` | `ovh-sdk.cache_store` | none | cache store for the server time delta and the OAuth2 token; empty means the default store of the application |

With application keys:

```bash
OVH_REGION=eu
OVH_AUTH=application_keys
OVH_APPLICATION_KEY=your-application-key
OVH_APPLICATION_SECRET=your-application-secret
OVH_CONSUMER_KEY=your-consumer-key
```

With a service account:

```bash
OVH_REGION=eu
OVH_AUTH=service_account
OVH_CLIENT_ID=your-client-id
OVH_CLIENT_SECRET=your-client-secret
```

See [Authentication](./authentication.md) to get these credentials.

The connector is built when it is first resolved, not when the application boots. An unknown region or mode, or a missing required credential, then throws an `InvalidArgumentException` whose message names the variable to set; an empty variable counts as missing. The message never contains a secret value.

Usage
-----

`OvhSdk\Ovh` is registered as a singleton. Use it through the facade:

```php
use OvhSdk\Enums\RecordType;
use OvhSdk\Laravel\Facades\Ovh;

$domains = Ovh::domain()->list();

Ovh::domain()->zone('example.com')->records()->create(RecordType::A, '203.0.113.10', 'app');
Ovh::domain()->zone('example.com')->refresh();
```

The facade forwards every method of the connector: `domain()`, `get()`, `post()`, `put()`, `delete()` and `requestConsumerKey()`, plus `Ovh::fake()` for tests.

Or by injection:

```php
use OvhSdk\Ovh;

final class SyncDomainExpirations
{
    public function __construct(private Ovh $ovh) {}

    public function __invoke(): void
    {
        foreach ($this->ovh->domain()->list() as $domain) {
            // ...
        }
    }
}
```

The rest of the API is the same as without Laravel: see [Domains](./domains.md), [DNS zones](./dns-zones.md) and [Errors](./errors.md).

Cache
-----

The connector receives a store of the Laravel cache: the one named by `OVH_CACHE_STORE`, or the default store of the application. It keeps there:

| Key | Content | Lifetime |
|---|---|---|
| `ovh-sdk.time-delta.{region}` | difference between the local clock and the OVHcloud servers, in application keys mode | one hour |
| `ovh-sdk.oauth2-token.{hash}` | OAuth2 token, in service account mode | until 60 seconds before the token expires |

Sharing these values saves a call to OVHcloud in each new process (request, queued job, command). With several application servers, a shared store (Redis, database) shares them between servers too.

Artisan command
---------------

`ovh:consumer-key` requests a consumer key bound to access rules:

```bash
php artisan ovh:consumer-key "GET:/domain" "GET:/domain/*" "POST:/domain/zone/*" "PUT:/domain/zone/*" "DELETE:/domain/zone/*"
```

| Argument or option | Role |
|---|---|
| `rules*` | one or more access rules, written `METHOD:/path` (for example `GET:/domain/*`) |
| `--redirect=` | URL OVHcloud redirects to once the rules are validated |

The command needs the `application_keys` mode with `OVH_APPLICATION_KEY` and `OVH_APPLICATION_SECRET` set; `OVH_CONSUMER_KEY` may be empty. It prints the validation URL to open, then the `OVH_CONSUMER_KEY=...` line to add to the `.env` file once the rules are validated. It fails, with an error message, on a malformed rule or in `service_account` mode. An error answered by OVHcloud (an invalid application key, for example) is thrown as an exception of the SDK (see [Errors](./errors.md)).

Testing
-------

`Ovh::fake()` replaces every call of the connector by Saloon mock responses: see [Testing](./testing.md). It resolves the real connector, so the configuration must be valid in the test environment, and it writes to the configured cache store: use the `array` store in tests.

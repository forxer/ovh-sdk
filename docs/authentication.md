---
title: Authentication
order: 2
---

Authentication
==============

The OVHcloud API offers two ways to authenticate, and the SDK supports both. The mode is chosen by the credentials object given to the connector: both are Saloon authenticators.

| Mode | Class | Credentials |
|---|---|---|
| Application keys | `OvhSdk\Auth\ApplicationKeys` | application key, application secret, consumer key |
| Service account (OAuth2) | `OvhSdk\Auth\ServiceAccount` | client id, client secret |

In a Laravel application, the mode and the credentials come from the configuration: see [Laravel](./laravel.md).

Application keys
----------------

```php
use OvhSdk\Auth\ApplicationKeys;
use OvhSdk\Enums\Region;
use OvhSdk\Ovh;

$ovh = new Ovh(
    region: Region::Eu,
    credentials: new ApplicationKeys(
        applicationKey: 'application-key',
        applicationSecret: 'application-secret',
        consumerKey: 'consumer-key',
    ),
);
```

Each request carries the `X-Ovh-Application`, `X-Ovh-Consumer`, `X-Ovh-Timestamp` and `X-Ovh-Signature` headers. The signature follows the official [First Steps with the OVHcloud APIs](https://docs.ovhcloud.com/en/guides/manage-and-operate/api/first-steps) guide:

```text
"$1$" + SHA1_HEX(AS+"+"+CK+"+"+METHOD+"+"+QUERY+"+"+BODY+"+"+TSTAMP)
```

where `QUERY` is the full URL called, `BODY` the request body (empty when there is none) and `TSTAMP` the Unix timestamp of the OVHcloud servers (see "Server clock" below). `OvhSdk\Auth\Signature::compute()` implements this formula.

The consumer key is optional in the constructor: without it, only `X-Ovh-Application` is sent and nothing is signed. That is what a consumer key request needs, but every other route needs the three keys.

### Getting the application key and secret

Declare your application on the token creation page of your region, logged in with your OVHcloud account:

| Region | Token creation page |
|---|---|
| Europe | `https://www.ovh.com/auth/api/createToken` |
| Canada | `https://ca.ovh.com/auth/api/createToken` |

For the US region, use the page referenced by the OVHcloud US documentation.

The page gives the application key (AK), the application secret (AS) and a consumer key (CK) bound to your own account, limited to the rights filled in on the page. The official guides describe the whole process: [First Steps with the OVHcloud APIs](https://docs.ovhcloud.com/en/guides/manage-and-operate/api/first-steps) and [How to manage a customer's account via OVHcloud API](https://docs.ovhcloud.com/en/guides/manage-and-operate/api/api-right-delegation).

### Requesting a consumer key

A consumer key can also be requested by the application itself, bound to a list of access rules. Create the connector with the application key and secret only, then call `requestConsumerKey()`:

```php
use OvhSdk\Auth\ApplicationKeys;
use OvhSdk\Data\Auth\AccessRule;
use OvhSdk\Enums\HttpMethod;
use OvhSdk\Enums\Region;
use OvhSdk\Ovh;

$ovh = new Ovh(Region::Eu, new ApplicationKeys('application-key', 'application-secret'));

$credential = $ovh->requestConsumerKey(
    accessRules: [
        AccessRule::fromString('GET:/domain'),
        AccessRule::fromString('GET:/domain/*'),
        new AccessRule(HttpMethod::Post, '/domain/zone/*'),
    ],
    redirection: 'https://example.com/ovh/validated',
);

$credential->consumerKey;    // string, to store once validated
$credential->state;          // OvhSdk\Enums\CredentialState, PendingValidation at first
$credential->validationUrl;  // ?string, to open to validate the rules
```

`requestConsumerKey(array $accessRules, ?string $redirection = null): CredentialRequest` sends `POST /auth/credential` with the application key only, as in the official delegation guide. The consumer key works once the account owner has opened `validationUrl`, logged in and accepted the rules; OVHcloud then redirects to `redirection` when one was given.

- `AccessRule::fromString()` reads a rule written as `METHOD:/path`, `*` being a wildcard in the path. It throws an `InvalidArgumentException` for a rule without `:`, with an unknown method or with an empty path.
- `OvhSdk\Enums\HttpMethod` has the cases `Get`, `Post`, `Put`, `Patch` and `Delete`.
- `OvhSdk\Enums\CredentialState` has the cases `PendingValidation`, `Validated`, `Refused`, `Expired` and `Unknown`.
- Calling `requestConsumerKey()` on a connector that does not hold `ApplicationKeys` throws a `LogicException`.

In a Laravel application, the `ovh:consumer-key` artisan command does the same from the command line:

```bash
php artisan ovh:consumer-key "GET:/domain" "GET:/domain/*" "POST:/domain/zone/*" --redirect=https://example.com/ovh/validated
```

It prints the validation URL and the `OVH_CONSUMER_KEY` line to add to the `.env` file. See [Laravel](./laravel.md).

### Server clock

The signed timestamp must follow the clock of the OVHcloud servers. Before the first signed request, the connector calls `GET /auth/time` (unauthenticated) and keeps the difference with the local clock:

- in memory, for the lifetime of the connector instance;
- in the PSR-16 cache given to the connector, when there is one, for one hour, under the key `ovh-sdk.time-delta.{region}` (`ovh-sdk.time-delta.eu` for example).

`$ovh->timeDelta()` returns this difference, in seconds. The service account mode signs nothing and never calls `/auth/time`.

Service account (OAuth2)
------------------------

```php
use OvhSdk\Auth\ServiceAccount;
use OvhSdk\Enums\Region;
use OvhSdk\Ovh;

$ovh = new Ovh(
    region: Region::Eu,
    credentials: new ServiceAccount(
        clientId: 'client-id',
        clientSecret: 'client-secret',
    ),
);
```

The SDK uses the OAuth2 client credentials flow described in the official guide [How to use service accounts to connect to OVHcloud APIs](https://docs.ovhcloud.com/en/guides/account-and-service-management/account-information/authenticate-api-with-service-account): it posts the client id and secret to the token endpoint of the region (`grant_type=client_credentials`, `scope=all`), then sends the token in an `Authorization: Bearer` header.

| Region | Token endpoint |
|---|---|
| `Region::Eu` | `https://www.ovh.com/auth/oauth2/token` |
| `Region::Ca` | `https://ca.ovh.com/auth/oauth2/token` |
| `Region::Us` | `https://us.ovhcloud.com/auth/oauth2/token` |

The EU and CA endpoints come from the official guide. The US endpoint comes from the OVHcloud US support site and is not confirmed by the official documentation, which only lists EU and CA; it does answer like the two others.

### Getting a service account

A service account is created through the API, and its rights are granted by IAM policies. See the official guides [Managing OVHcloud service accounts](https://docs.ovhcloud.com/en/guides/manage-and-operate/api/manage-service-account) and [How to use IAM policies using the OVHcloud API](https://docs.ovhcloud.com/en/guides/account-and-service-management/account-information/iam-policies-api). The "IAM actions by method" table below lists the actions each method of the SDK needs.

### Token cache

The token is fetched at the first request, then kept:

- in memory, in the `ServiceAccount` instance;
- in the PSR-16 cache given to the connector, when there is one, under a key derived from the region and the client id (`ovh-sdk.oauth2-token.` followed by a SHA-1 hash).

It is considered expired 60 seconds before the `expires_in` announced by OVHcloud, and fetched again then.

IAM actions by method
---------------------

Every route of the SDK is in the `/v1` branch. The IAM action is the one given by the official API schema. A service account needs an IAM policy that grants the action; with application keys, the access rules of the consumer key must cover the route.

| SDK method | OVHcloud route | IAM action |
|---|---|---|
| `domain()->ids()` | `GET /domain` | `domain:apiovh:get` |
| `domain()->list()` | `GET /domain`, then `GET /domain/{serviceName}` per domain | `domain:apiovh:get` |
| `domain()->get($domain)` | `GET /domain/{serviceName}` | `domain:apiovh:get` |
| `domain()->serviceInfos($domain)` | `GET /domain/{serviceName}/serviceInfos` | `domain:apiovh:serviceInfos/get` |
| `domain()->zones()->ids()` | `GET /domain/zone` | `dnsZone:apiovh:get` |
| `domain()->zones()->list()` | `GET /domain/zone`, then `GET /domain/zone/{zoneName}` per zone | `dnsZone:apiovh:get` |
| `zone($zoneName)->get()` | `GET /domain/zone/{zoneName}` | `dnsZone:apiovh:get` |
| `zone($zoneName)->refresh()` | `POST /domain/zone/{zoneName}/refresh` | `dnsZone:apiovh:refresh` |
| `records()->ids()` | `GET /domain/zone/{zoneName}/record` | `dnsZone:apiovh:record/get` |
| `records()->list()` | `GET /domain/zone/{zoneName}/record`, then `GET /domain/zone/{zoneName}/record/{id}` per record | `dnsZone:apiovh:record/get` |
| `records()->get($id)` | `GET /domain/zone/{zoneName}/record/{id}` | `dnsZone:apiovh:record/get` |
| `records()->create(...)` | `POST /domain/zone/{zoneName}/record` | `dnsZone:apiovh:record/create` |
| `records()->update($id, ...)` | `PUT /domain/zone/{zoneName}/record/{id}` | `dnsZone:apiovh:record/edit` |
| `records()->delete($id)` | `DELETE /domain/zone/{zoneName}/record/{id}` | `dnsZone:apiovh:record/delete` |

`zone($zoneName)` and `records()` are reached through `$ovh->domain()->zone($zoneName)`. `GET /auth/time` and `POST /auth/credential` need no IAM action.

---
title: Testing
order: 7
---

Testing
=======

The SDK is built on Saloon: its `MockClient` answers every call with mock responses, so code using the SDK can be tested without any real call to OVHcloud. In a Laravel application, `Ovh::fake()` sets it up for you.

Without Laravel
---------------

Attach a `Saloon\Http\Faking\MockClient` to the connector with `withMockClient()`:

```php
use OvhSdk\Auth\ApplicationKeys;
use OvhSdk\Enums\Region;
use OvhSdk\Ovh;
use OvhSdk\Requests\Auth\GetServerTime;
use OvhSdk\Requests\Domain\ListDomains;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

$mockClient = new MockClient([
    GetServerTime::class => MockResponse::make((string) time()),
    ListDomains::class => MockResponse::make(['example.com', 'example.org']),
]);

$ovh = new Ovh(Region::Eu, new ApplicationKeys('application-key', 'application-secret', 'consumer-key'));
$ovh->withMockClient($mockClient);

$ovh->domain()->ids(); // ['example.com', 'example.org']

$mockClient->assertSent(ListDomains::class);
```

### The calls of the authentication

The authentication sends its own requests through the same mock client:

- with application keys and a consumer key, the first signed request is preceded by `GET /auth/time` (unless the connector already holds the time delta, in memory or in its cache): mock `OvhSdk\Requests\Auth\GetServerTime` with a Unix timestamp, as above;
- with a service account, the first request is preceded by the token request: mock `OvhSdk\Requests\Auth\GetOAuth2Token` with `['access_token' => 'token', 'token_type' => 'Bearer', 'expires_in' => 3600]`;
- with Saloon's `Saloon\Http\Auth\NullAuthenticator` as credentials, nothing else is sent, which keeps the tests of your own code free of authentication calls.

These calls are recorded like the others: count by request class (`assertSentCount(1, ListDomains::class)`) rather than in total.

### Choosing the response

Saloon looks for a mock response in this order: by request class, by connector class, by URL pattern, then in the sequence of responses given without a key.

```php
use OvhSdk\Requests\Domain\GetDomain;
use Saloon\Http\PendingRequest;

$mockClient = new MockClient([
    // By request class: the same response for every request of that class.
    GetServerTime::class => MockResponse::make((string) time()),
    ListDomains::class => MockResponse::make(['b.example', 'a.example']),

    // A closure receives the pending request and builds the response from it
    // ($domainFixture being a decoded domain.DomainService JSON object).
    GetDomain::class => fn (PendingRequest $pending) => MockResponse::make(
        ['domain' => $pending->getRequest()->domain] + $domainFixture,
    ),

    // By URL pattern, "*" being a wildcard: handy for the generic calls.
    'api.eu.ovhcloud.com/v1/me' => MockResponse::make(['nichandle' => 'xx1234-ovh']),
]);
```

A sequence (responses without keys) is consumed in the order the requests are sent. Keep `GetServerTime` or `GetOAuth2Token` keyed by class next to a sequence: otherwise the authentication call takes the first response of the sequence.

`list()` sends its calls in parallel: prefer a class key with a closure, as above, to a sequence.

Each method sends the following request classes, all in `OvhSdk\Requests`:

| SDK method | Request classes |
|---|---|
| `domain()->ids()` | `Domain\ListDomains` |
| `domain()->list()` | `Domain\ListDomains`, then `Domain\GetDomain` per domain |
| `domain()->get()` | `Domain\GetDomain` |
| `domain()->serviceInfos()` | `Domain\GetDomainServiceInfos` |
| `domain()->zones()->ids()` | `Dns\ListZones` |
| `domain()->zones()->list()` | `Dns\ListZones`, then `Dns\GetZone` per zone |
| `zone()->get()` | `Dns\GetZone` |
| `zone()->status()` | `Dns\GetZoneStatus` |
| `zone()->export()`, `zone()->exportedRecords()` | `Dns\ExportZone` |
| `zone()->history()` | `Dns\ListZoneHistory` |
| `zone()->refresh()` | `Dns\RefreshZone` |
| `records()->ids()` | `Dns\ListRecords` |
| `records()->list()` | `Dns\ListRecords`, then `Dns\GetRecord` per record |
| `records()->get()` | `Dns\GetRecord` |
| `records()->create()` | `Dns\CreateRecord` |
| `records()->update()` | `Dns\UpdateRecord` |
| `records()->delete()` | `Dns\DeleteRecord` |
| `redirections()->ids()` | `Dns\ListRedirections` |
| `redirections()->list()` | `Dns\ListRedirections`, then `Dns\GetRedirection` per redirection |
| `redirections()->get()` | `Dns\GetRedirection` |
| `tasks()->ids()` | `Dns\ListZoneTasks` |
| `tasks()->list()` | `Dns\ListZoneTasks`, then `Dns\GetZoneTask` per task |
| `tasks()->get()` | `Dns\GetZoneTask` |
| `requestConsumerKey()` | `Auth\RequestCredential` |
| `get()`, `post()`, `put()`, `delete()` | `Generic\GenericRequest`, or `Generic\GenericJsonRequest` for a non empty body |
| clock synchronisation | `Auth\GetServerTime` |
| service account token | `Auth\GetOAuth2Token` |

### Simulating an error

An error response throws the same exception as a real one (see [Errors](./errors.md)):

```php
use OvhSdk\Exceptions\NotFoundException;

$mockClient = new MockClient([
    GetDomain::class => MockResponse::make(
        ['class' => 'Client::NotFound', 'message' => 'This service does not exist'],
        404,
        ['X-Ovh-QueryId' => 'EU.ext-1.abc'],
    ),
]);

// $ovh->domain()->get('example.com') throws a NotFoundException
// whose errorClass is "Client::NotFound" and queryId "EU.ext-1.abc".
```

### Assertions

The `MockClient` records every request, and offers Saloon's assertions:

| Assertion | Checks that |
|---|---|
| `assertSent(CreateRecord::class)` | a request of that class was sent |
| `assertSent(fn (Request $request, Response $response) => ...)` | a request matching the closure was sent |
| `assertNotSent(GetDomain::class)` | no request of that class was sent |
| `assertSentCount(2, GetDomain::class)` | that many requests of that class were sent |
| `assertSentCount(3)` | that many requests were sent in total, authentication calls included |
| `assertNothingSent()` | no request at all was sent |

```php
use OvhSdk\Requests\Dns\CreateRecord;
use Saloon\Http\Request;

$mockClient->assertSent(fn (Request $request) => $request instanceof CreateRecord
    && $request->zoneName === 'example.com'
    && $request->target === '203.0.113.10');
```

The typed requests (every request class except `Generic\GenericRequest` and `Generic\GenericJsonRequest`) expose their arguments as public read-only properties (`$request->domain`, `$request->zoneName`, `$request->target`...), which the closures can check. `Dns\UpdateRecord` exposes the fields it sends in `$request->changes`, for example `['target' => '203.0.113.20']`.

### Freezing the time

The connector takes a PSR-20 clock as its fourth argument, `clock`. Pass a frozen clock to make the signed timestamps and the token expiry deterministic.

With Laravel
------------

`Ovh::fake()` attaches a `MockClient` to the connector of the application and returns it, for the assertions:

```php
use OvhSdk\Laravel\Facades\Ovh;
use OvhSdk\Requests\Domain\ListDomains;
use Saloon\Http\Faking\MockResponse;

it('lists the domains', function () {
    $mock = Ovh::fake([
        ListDomains::class => MockResponse::make(['example.com']),
    ]);

    expect(Ovh::domain()->ids())->toBe(['example.com']);

    $mock->assertSent(ListDomains::class);
});
```

`Ovh::fake(array $responses = []): MockClient` takes the same keys as the `MockClient`: request classes, URL patterns or a sequence.

- **Default responses**: `GET /auth/time` (`GetServerTime`, current time) and the OAuth2 token (`GetOAuth2Token`, token `fake-token` valid one hour) are answered automatically, whatever the authentication mode.
- **Replacing a default response**: give a `GetServerTime::class` or `GetOAuth2Token::class` key. A URL key for the same endpoint is ignored, because Saloon matches the request class first.
- **Injection**: the connector is a singleton, so a class receiving `OvhSdk\Ovh` by injection is faked too.

```php
use OvhSdk\Exceptions\ServerException;
use OvhSdk\Requests\Auth\GetServerTime;

Ovh::fake([
    GetServerTime::class => MockResponse::make('', 500),
    ListDomains::class => MockResponse::make([]),
]);

// The clock synchronisation fails before ListDomains is sent.
expect(fn () => Ovh::domain()->ids())->toThrow(ServerException::class);
```

### What Ovh::fake() needs

`Ovh::fake()` resolves the real connector of the application before attaching the mock client:

- the configuration must be valid in the test environment, or the resolution throws an `InvalidConfiguration` (see [Laravel](./laravel.md)): set test credentials, which only reach the mock client;
- the connector writes to the configured cache store: the server time delta and, in service account mode, the `fake-token`. On a persistent store, a fake token could outlive the test: use the `array` store.

In `phpunit.xml`:

```xml
<php>
    <env name="OVH_APPLICATION_KEY" value="testing"/>
    <env name="OVH_APPLICATION_SECRET" value="testing"/>
    <env name="OVH_CONSUMER_KEY" value="testing"/>
    <env name="OVH_CACHE_STORE" value="array"/>
</php>
```

A test that changes the configuration must drop the resolved connector before faking it, so that it is built again:

```php
config(['ovh-sdk.auth' => 'service_account', 'ovh-sdk.service_account.client_id' => 'client-id', 'ovh-sdk.service_account.client_secret' => 'client-secret']);
app()->forgetInstance(OvhSdk\Ovh::class);
Ovh::clearResolvedInstances();

Ovh::fake([ListDomains::class => MockResponse::make(['example.com'])]);
```

Testing the SDK against the real API
------------------------------------

This section is for the contributors of the SDK. Its own tests run on fixtures, without any call to OVHcloud. A separate suite, `tests/Live`, reads the real API to check that the SDK still understands its responses: it never runs in `composer test`, `composer check` or the CI, only on demand.

```bash
composer test:live
```

### Setting it up

1. Copy `.env.example` to `.env` (git ignores it), then set `OVH_APPLICATION_KEY`, `OVH_APPLICATION_SECRET` and `OVH_LIVE_DOMAIN`, a domain of the account whose DNS zone is hosted by OVHcloud.
2. Request a consumer key restricted to this domain, in read only:

   ```bash
   php tests/Live/consumer-key.php
   ```

3. Validate the key at the printed URL, choosing its validity, then set `OVH_CONSUMER_KEY` in `.env`.

A variable of the environment takes precedence over `.env`. Without the four variables, the live tests are skipped.

The key gets `GET` access to the routes the live tests read, and nothing else: `/auth/currentCredential`, the domain and zone lists, the test domain and its service information, and the test zone with everything below it. The transfer code of the domain (`GET /domain/{serviceName}/authInfo`) stays out of reach.

### Safeguards

- Before any other request, the suite reads the rules of the consumer key (`GET /auth/currentCredential`) and refuses to go on when one of them is not a `GET`, or reaches the transfer code of the test domain. Never reuse the consumer key of an application, which can usually write.
- Every request goes through a middleware that refuses, before sending, anything but a `GET` on the routes above.

### What it checks

The live tests check the shape of the responses, not their values, which change over time: every response is hydrated, and no enum is read as `Unknown`. A failure on an enum means OVHcloud sends a value this version of the SDK does not know yet.

They also check that the zone file and the record API agree: the export of the test zone splits into as many records as `records()->ids()` returns, with the same types once `DKIM`, `DMARC` and `SPF` are counted as `TXT` (see [DNS zones](./dns-zones.md)), and the history accepts its own dates as bounds.

CHANGELOG
=========

0.5.0 (2026-10-18)
------------------

- DNS zones: `tasks()->ids()` and `tasks()->list()` refuse `TaskStatus::Problem` as a filter before sending, with a `CouldNotFilterZoneTasks` (an `InvalidArgumentException`): OVHcloud answers `status=problem` with a 400 `Invalid inputs`, although its schema lists the value. A task read from OVHcloud can still have this status (see the DNS zones documentation)
- Errors: a successful response whose body is not JSON, an empty body included, throws a `CouldNotDecodeResponse`, a subclass of `CouldNotReadResponse` holding the `JsonException` as previous exception. JSON of the wrong shape, such as a string where a list or an object is expected, throws a `CouldNotReadResponse`. Catch `CouldNotDecodeResponse` first to retry the first case only (see the errors documentation)
- Authentication: a server time (`GET /auth/time`) that is not a JSON integer throws instead of being read as `0`, which gave a wrong time delta, cached for an hour, and failed the signature of every request
- Contributors: the live suite sends each task status as a filter, and checks that OVHcloud still refuses `problem`

### Changed exceptions

Code catching these failures must catch the new exceptions:

- A successful response whose body is not valid JSON threw a `JsonException`. It now throws an `OvhSdk\Exceptions\CouldNotDecodeResponse`, which is not a `JsonException`: replace `catch (JsonException)` with `catch (CouldNotDecodeResponse)`. The `JsonException` stays available through `getPrevious()`.
- A successful response holding a JSON scalar where an object or a list is expected threw a `TypeError` (`Cannot assign string to property Saloon\Http\Response::$decodedJson of type array`). It now throws a `CouldNotReadResponse`.
- An empty body on a list route was read as an empty list. It now throws a `CouldNotDecodeResponse`.
- An object route answering a JSON list throws a `CouldNotReadResponse` saying that the response is not an object, instead of naming the first missing field.
- `TaskStatus::Problem` given to `tasks()->ids()` or `tasks()->list()` was sent, and OVHcloud answered with a `BadRequestException`. It now throws a `CouldNotFilterZoneTasks` before sending.

0.4.0 (2026-10-18)
------------------

- Errors: every failure the SDK detects itself throws a dedicated exception, named after what failed, its message saying why with the values involved: `CouldNotReadResponse`, `CouldNotParseZoneFile` (with the line in `zoneFileLine`), `InvalidAccessRule`, `CouldNotSendUnknownCase`, `CouldNotUpdateRecord`, `InvalidZoneName`, `CouldNotRequestConsumerKey`, `CouldNotAuthenticateRequest` and, in Laravel, `OvhSdk\Laravel\Exceptions\InvalidConfiguration` (see the errors documentation)
- Errors: each dedicated exception extends the SPL exception the SDK threw before (`InvalidArgumentException`, `UnexpectedValueException` or `LogicException`), so existing `catch` blocks keep working. An OAuth2 token response without `access_token` or `expires_in` now throws a `CouldNotReadResponse`, which is still a `RuntimeException`

### Changed exception messages

Code comparing the message of these exceptions must follow the new wording:

- `AccessRule::fromString()` with an unknown method names the method and lists the known ones: `Access rule "FETCH:/domain" has an unknown HTTP method "FETCH": use one of GET, POST, PUT, PATCH, DELETE.`
- An `Unknown` case refused before sending is named with its class, for example `OvhSdk\Enums\TaskStatus::Unknown cannot be sent to OVHcloud: it only stands for a value read from OVHcloud that this version of the SDK does not know.` The tasks no longer share one message between their two filters.
- A record `update()` without any field names the record and its zone: `Record 42 of zone "example.com" cannot be updated without any change: pass at least one of target, subDomain or ttl.`
- `requestConsumerKey()` on a connector without `ApplicationKeys` names the authenticator of the connector, and an authenticator of the SDK used with another connector names that connector.
- `OVH credential response is missing consumerKey.`, without `the`.
- `OVH OAuth2 token response is missing access_token.` (or `expires_in`), instead of `OAuth2 token response is missing ...`.
- An empty zone name given to `ZoneFile::parse()`: `A zone file needs the name of its zone, such as "example.com", "." given.`

0.3.0 (2026-10-18)
------------------

- DNS zones: `ZoneFile::parse()` splits a BIND zone file into records (`ZoneFileRecord`: sub-domain, TTL, type and target) and reads its default TTL, without the SOA record; `zone()->exportedRecords()` splits the export of a zone, reading every record in a single call
- DNS zones: `zone()->history()` reads the creation dates of the restore points of a zone, oldest first, optionally between two dates
- DNS zones: `zone()->redirections()` reads the web redirections of a zone (`ids()`, `list()`, `get()`, `Redirection`), with the new `RedirectionType` enum
- DNS zones: `zone()->tasks()` reads the tasks of a zone (`ids()`, `list()`, `get()`, `ZoneTask`), with the new `TaskFunction` and `TaskStatus` enums
- Contributors: `composer test:live` runs an opt-in suite against the real API, with a read only consumer key restricted to one domain (see the testing documentation)

0.2.0 (2026-10-18)
------------------

- Domains: `Domain` exposes the transfer lock status (`transferLockStatus`), the DNSSEC state (`dnssecState`), the DNSSEC flag (`dnssecSupported`, ambiguous in the official schema, see the documentation) and the offer (`offer`), with the new `TransferLockStatus`, `DnssecState` and `DomainOffer` enums
- DNS zones: `zone()->status()` reads the deployment status of a zone, its errors, warnings and last refresh (`ZoneStatus`)
- DNS zones: `zone()->export()` returns the zone as a BIND zone file, in a single call
- Documentation: decoding a JSON scalar response of the generic calls, which Saloon's `json()` refuses

### Breaking changes

- The constructor of `OvhSdk\Data\Domain\Domain` takes four new required arguments, after `serviceId`: `transferLockStatus`, `dnssecState`, `dnssecSupported` and `offer`. Code building a `Domain` by hand must pass them.
- `Domain::fromArray()` now requires a boolean `dnssecSupported` field, and throws an `UnexpectedValueException` without it. The official schema declares it non nullable and OVHcloud sends it; test fixtures or mocked responses of `GET /domain/{serviceName}` must hold it too.

0.1.0 (2026-10-18)
------------------

First release.

- Saloon connector `OvhSdk\Ovh` with the EU, CA and US regions, and generic `get()`, `post()`, `put()` and `delete()` calls on any endpoint
- Typed exceptions for OVHcloud errors, exposing the OVH error class and the query id
- Application keys authentication, with request signature and server clock synchronisation
- OAuth2 service account authentication, with a cached token
- Consumer key request with `Ovh::requestConsumerKey()`
- Domains: names, details and service information
- DNS zones: names, details and refresh
- DNS records: list, read, create, update and delete
- Laravel 13 integration: configuration, facade, injection, cache, `Ovh::fake()` and `ovh:consumer-key` command
- Documentation

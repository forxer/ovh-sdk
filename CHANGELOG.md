CHANGELOG
=========

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

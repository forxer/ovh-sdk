# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

`forxer/ovh-sdk` is an unofficial, modern PHP SDK for the OVHcloud API, built on Saloon 4. It requires PHP 8.4+ and ships an optional Laravel 13 integration in `src/Laravel/`. The core is framework agnostic: nothing outside `src/Laravel/` may use `Illuminate\`.

## Commands

```bash
# All checks at once (lint + analyse + test)
composer check

# Individual commands
composer test       # Run tests (Pest)
composer lint       # Check code style (Pint)
composer fix        # Fix code style
composer analyse    # Static analysis (PHPStan level max, src only)
composer refactor   # Rector
```

## Architecture

- `src/Ovh.php`: the Saloon connector (`final`, `AlwaysThrowOnErrors`). It holds the region, the authenticator, an optional PSR-16 cache and a PSR-20 clock, computes the server time delta (`timeDelta()`), requests consumer keys (`requestConsumerKey()`), gives access to the resources (`domain()`) and offers generic `get()`, `post()`, `put()` and `delete()` calls on any endpoint. `getRequestException()` turns every error response into a typed exception.
- `src/Auth/`: the two Saloon authenticators, `ApplicationKeys` (signed requests, `Signature::compute()`, clock sync through `GET /auth/time`) and `ServiceAccount` (OAuth2 client credentials, token kept in memory and in the cache, renewed 60 seconds before expiry).
- `src/Data/`: the DTOs, by domain (`Auth`, `Dns`, `Domain`, `Service`). `final readonly class`, dates as `DateTimeImmutable`, nullability following `canBeNull` of the official schema, a static `fromArray()` factory.
- `src/Enums/`: `Region` (API host and OAuth2 token endpoint), `ApiVersion` (`/v1`, `/v2`), `HttpMethod` (input only) and the enums read from OVH. Every enum read from OVH has an `Unknown = '__unknown__'` case and resolves through `fromOvh()` (trait `Concerns\ResolvesUnknownValues`): an unknown value never breaks hydration.
- `src/Exceptions/`: `OvhException` (extends Saloon `RequestException`, exposes `errorClass` and `queryId`) and one subclass per status (400, 401, 403, 404, 409, 429, 5xx), built by `OvhException::fromResponse()`.
- `src/Laravel/`: the only place allowed to use `Illuminate\` (arch test). Service provider (singleton `OvhSdk\Ovh` built from `config/ovh-sdk.php` at resolution time), facade with `fake()`, `ovh:consumer-key` command.
- `src/Requests/`: one Saloon request per OVH route, extending `OvhRequest` (which prefixes `path()` with the API branch) and building its DTO in `createDtoFromResponse()`. `Generic/` holds the untyped requests behind `Ovh::get()` and friends; `Auth/GetOAuth2Token` targets an absolute URL with `allowBaseUrlOverride`.
- `src/Resources/`: the fluent API (`DomainResource`, `ZonesResource`, `ZoneResource`, `RecordsResource`), extending `Resource`.
- `src/Support/`: internal helpers: `Fields` (typed reads of a decoded JSON object), `Dates` (parsing of `date` and `datetime` values), `SystemClock`.

### Conventions set while building

- DTO factories read fields through `Support\Fields` (`@internal`). A required field that is missing or of the wrong type, or a nullable field of the wrong type, throws an `UnexpectedValueException` rather than hydrating half a DTO. Enum fields go through `Fields::enumValue()` and never throw.
- `ids()` makes the single list call and returns identifiers; `list()` hydrates every element through `Resource::sendAll()`, a Saloon pool with `Resource::CONCURRENCY` (5) requests in flight. `sendAll()` keeps the order of the requests, waits for every request, and when some fail throws the exception of the earliest request in the list (never a partial list).
- `RecordType::Unknown` is refused before sending (`InvalidArgumentException`), as is a record `update()` without any field. An empty `subDomain` is sent as is (zone root).
- Errors raised by misuse are `InvalidArgumentException` or `LogicException`; Laravel configuration errors name the environment variable, never a secret value.

The user documentation is in `docs/` (front-matter `title` and `order`, underlined H1 and H2, relative links), with `README.md`, `CHANGELOG.md` and `UPGRADE.md` at the root: keep it in sync with `src/` when the public API changes.

## Source of truth for the OVH API

Use `https://docs.ovhcloud.com` and its local copy in `reference/` (`reference/docs/api/*.md`, `reference/schemas/v1/*.json`, refreshed with `reference/refresh.sh`). Never copy from or rely on `ovh/php-ovh`.

## Tests

- Pest 5, no real API call: Saloon `MockClient` everywhere.
- Helpers in `tests/Pest.php`: `ovh()` (connector with a `MockClient`, a `NullAuthenticator` and a frozen clock by default), `sentRequest()` (last pending request of a class) and `fixtureData()` (decoded JSON from `tests/Fixtures/`; named so because Pest already owns a global `fixture()` returning a path). Doubles in `tests/Support/` (`ArrayCache`, `FrozenClock`).
- Fixtures in `tests/Fixtures/` follow the models of the official schema. Laravel tests live in `tests/Laravel/` and run on Orchestra Testbench.

## Code Style

- Laravel Pint preset with `native_function_invocation`, PHPStan level max, `declare(strict_types=1);` everywhere.
- Code, comments, README, docs and this file are in English.
- No em dash or en dash as punctuation in the documentation and the commit messages.

## Commits

- Conventional Commits, in French, imperative mood (`feat: ajouter ...`).
- Ask Vincent for approval before each commit.
- Never mention Claude or AI in commit messages, no `Co-Authored-By`.

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

- `Ovh` (`src/Ovh.php`) is the Saloon connector: region, authenticator, optional PSR-16 cache and PSR-20 clock. It also offers generic `get()`, `post()`, `put()` and `delete()` calls on any endpoint.
- `src/Enums/` holds `Region` (API and OAuth2 hosts) and `ApiVersion` (`/v1`, `/v2` branches). Every enum from OVH has an `Unknown = '__unknown__'` case and a `fromOvh()` resolver, so an unknown value never breaks hydration.
- `src/Requests/` holds typed requests extending `OvhRequest`, which prefixes `path()` with the API branch. `Generic/` holds the untyped requests behind `Ovh::get()` and friends.
- DTOs are `final readonly class`, dates are `DateTimeImmutable`, nullability follows `canBeNull` from the official schema.

## Source of truth for the OVH API

Use `https://docs.ovhcloud.com` and its local copy in `reference/` (`reference/docs/api/*.md`, `reference/schemas/v1/*.json`, refreshed with `reference/refresh.sh`). Never copy from or rely on `ovh/php-ovh`.

## Tests

- Pest 5, no real API call: Saloon `MockClient` everywhere.
- Helpers in `tests/Pest.php`: `ovh()`, `sentRequest()`, `fixtureData()` (Pest already owns a global `fixture()` returning a path). Doubles in `tests/Support/`.

## Code Style

- Laravel Pint preset with `native_function_invocation`, PHPStan level max, `declare(strict_types=1);` everywhere.
- Code, comments, README, docs and this file are in English.

## Commits

- Conventional Commits, in French, imperative mood (`feat: ajouter ...`).
- Ask Vincent for approval before each commit.
- Never mention Claude or AI in commit messages, no `Co-Authored-By`.

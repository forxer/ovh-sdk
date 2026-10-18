---
title: Errors
order: 5
---

Errors
======

Every error status answered by OVHcloud (4xx or 5xx) throws an exception: the SDK never returns a failed response silently. This applies to the resources, to the generic `get()`, `post()`, `put()` and `delete()` calls, and to the calls the authentication makes on its own (`GET /auth/time`, OAuth2 token).

Exceptions by status
--------------------

All of them live in `OvhSdk\Exceptions` and extend `OvhSdk\Exceptions\OvhException`, which itself extends Saloon's `Saloon\Exceptions\Request\RequestException`.

| Status | Exception | Meaning |
|---|---|---|
| 400 | `BadRequestException` | the request is malformed or invalid |
| 401 | `AuthenticationException` | credentials are missing, invalid or expired; wrong application keys are answered with a 403 or a 400 instead (see "Wrong application keys" below) |
| 403 | `ForbiddenException` | the credentials are not granted for this request |
| 404 | `NotFoundException` | the resource does not exist |
| 409 | `ConflictException` | the request conflicts with the current state of the resource |
| 429 | `TooManyRequestsException` | the rate limit has been reached |
| 500 and above | `ServerException` | OVHcloud failed to process the request |
| any other 4xx status | `OvhException` | other client error, for example 405 or 422 |

What an exception exposes
-------------------------

OVHcloud documents its error body as:

```json
{
    "class": "Client::Forbidden",
    "message": "User not granted for this request"
}
```

| Member | Type | Content |
|---|---|---|
| `getMessage()` | `string` | the `message` field of the body; without it, the OAuth2 `error_description` field, or else `error`; `HTTP {status}` (for example `HTTP 502`) when there is none of them |
| `errorClass` | `?string` | the `class` field of the body, for example `Client::Forbidden` |
| `queryId` | `?string` | the `X-Ovh-QueryId` response header, when present |
| `getStatus()` | `int` | the HTTP status |
| `getResponse()` | `Saloon\Http\Response` | the raw response |

The body is read safely: a body that is not JSON, or fields that are not strings, leave `errorClass` to `null` and give the `HTTP {status}` message.

`queryId` identifies the request on the OVHcloud side. OVHcloud sends it in the `X-Ovh-QueryId` header (observed in lower case, `x-ovh-queryid`, on real responses; headers are case insensitive). Give it to the OVHcloud support when you report a problem with a call.

```php
use OvhSdk\Exceptions\ForbiddenException;
use OvhSdk\Exceptions\NotFoundException;
use OvhSdk\Exceptions\OvhException;

try {
    $domain = $ovh->domain()->get('example.com');
} catch (NotFoundException) {
    $domain = null;
} catch (ForbiddenException $exception) {
    // Missing access rule or IAM action, or unknown consumer key.
    throw $exception;
} catch (OvhException $exception) {
    $logger->error($exception->getMessage(), [
        'status' => $exception->getStatus(),
        'class' => $exception->errorClass,
        'query_id' => $exception->queryId,
    ]);

    throw $exception;
}
```

### Errors in a list

`list()` sends one call per element. When one of them fails, `list()` waits for the others, then throws the exception of the first failing element, in the order of the identifiers: it never returns a partial list. See [Domains](./domains.md).

### OAuth2 token errors

The token request of a service account goes through the same connector, so its errors are `OvhException`s too, with one difference: a `400` or a `401` from the token endpoint throws an `AuthenticationException`. For wrong client credentials, OVHcloud answers `400` with a body like `{"error": "invalid_client"}`, the OAuth2 error format (RFC 6749, section 5.2): the result is an `AuthenticationException` whose message is the `error_description` field of the body, or else its `error` field, here `invalid_client`. A token response without `access_token` or `expires_in` throws a `CouldNotReadResponse` (see "Other exceptions" below).

### Wrong application keys

With application keys, OVHcloud does not answer wrong credentials with a `401`, so they never throw an `AuthenticationException`. Observed on real responses:

| Case | Status | Exception | `errorClass` | Message |
|---|---|---|---|---|
| unknown consumer key | 403 | `ForbiddenException` | `Client::Forbidden` | `This credential does not exist` |
| wrong application secret | 400 | `BadRequestException` | `Client::BadRequest` | `Invalid signature` |

OVHcloud does not document these messages, so the SDK types these responses by their status only. To tell a credential problem from a missing access right or a malformed request, read `getMessage()`.

Other exceptions
----------------

Not every failure is an OVHcloud error status. For the other failures it detects, the SDK throws a dedicated exception: its class names what failed, its message says why, with the values involved. They live in `OvhSdk\Exceptions`, except `InvalidConfiguration`, which lives in `OvhSdk\Laravel\Exceptions`.

Each one extends a standard SPL exception, so a `catch (InvalidArgumentException)` or a `catch (UnexpectedValueException)` keeps catching them.

| Exception | Extends | When |
|---|---|---|
| `CouldNotReadResponse` | `UnexpectedValueException` | a successful response does not follow the official schema: a required field is missing or of the wrong type, a list route does not return a list, an object route does not return an object, a date cannot be parsed, a consumer key response has no `consumerKey`, an OAuth2 token response has no `access_token` or `expires_in`, a zone export is not a JSON string, the server time is not a JSON integer. The SDK throws rather than hydrating half a DTO |
| `CouldNotDecodeResponse` | `CouldNotReadResponse` | the body of a successful response is not JSON at all, an empty body included: the page of a proxy or a maintenance page, for example. The message names the method and the URL of the call, `getPrevious()` gives the `JsonException` |
| `CouldNotParseZoneFile` | `UnexpectedValueException` | a zone file that `ZoneFile::parse()` or `exportedRecords()` cannot read. The message names the line, which the `zoneFileLine` property gives too |
| `InvalidAccessRule` | `InvalidArgumentException` | a malformed access rule in `AccessRule::fromString()`: no `:`, an unknown method (the message lists the known ones) or an empty path |
| `CouldNotSendUnknownCase` | `InvalidArgumentException` | the `Unknown` case of an enum given to the SDK: `RecordType::Unknown` given to `ids()`, `list()` or `create()` of the records, `TaskFunction::Unknown` or `TaskStatus::Unknown` given to `ids()` or `list()` of the tasks. The message names the case |
| `CouldNotFilterZoneTasks` | `InvalidArgumentException` | `TaskStatus::Problem` given to `ids()` or `list()` of the tasks: OVHcloud refuses this filter although its schema lists it (see [DNS zones](./dns-zones.md)). The message names the zone and the status |
| `CouldNotUpdateRecord` | `InvalidArgumentException` | a record `update()` without any field, the message naming the record and its zone |
| `InvalidZoneName` | `InvalidArgumentException` | an empty zone name given to `ZoneFile::parse()` |
| `InvalidConfiguration` | `InvalidArgumentException` | in Laravel, an incomplete or wrong configuration, the message naming the environment variable to set |
| `CouldNotRequestConsumerKey` | `LogicException` | `requestConsumerKey()` on a connector without `ApplicationKeys`, the message naming the authenticator it holds |
| `CouldNotAuthenticateRequest` | `LogicException` | an authenticator of the SDK used with another connector than `OvhSdk\Ovh`, the message naming that connector |

One more exception comes from Saloon:

| Exception | When |
|---|---|
| `Saloon\Exceptions\Request\FatalRequestException` | no response at all: DNS failure, refused connection, timeout |

A dedicated exception can be caught on its own, without catching the other failures of the same family. For example, for a zone file given by a user:

```php
use OvhSdk\Data\Dns\ZoneFile;
use OvhSdk\Exceptions\CouldNotParseZoneFile;

try {
    $records = ZoneFile::parse($content, 'example.com')->records;
} catch (CouldNotParseZoneFile $exception) {
    // "Zone file, line 12: the A record has no data."
    $errors['zone_file'] = $exception->getMessage();
    $errorLine = $exception->zoneFileLine;
}
```

### Responses the SDK cannot read

`CouldNotDecodeResponse` extends `CouldNotReadResponse`, so a `catch (CouldNotReadResponse)` catches both. Catch `CouldNotDecodeResponse` first to tell them apart: a body that is not JSON most likely comes from something between the SDK and OVHcloud and may not come back, so retrying later makes sense, whereas JSON outside the official schema will not change on a retry.

```php
use OvhSdk\Exceptions\CouldNotDecodeResponse;
use OvhSdk\Exceptions\CouldNotReadResponse;

try {
    $records = $ovh->domain()->zone('example.com')->records()->list();
} catch (CouldNotDecodeResponse $exception) {
    // Not JSON: worth retrying later.
} catch (CouldNotReadResponse $exception) {
    // JSON outside the official schema: retrying will not help, report it.
}
```

An unknown enum value is never an error: it is read as the `Unknown` case of the enum (see [Domains](./domains.md) and [DNS zones](./dns-zones.md)).

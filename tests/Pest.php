<?php

declare(strict_types=1);

use OvhSdk\Enums\Region;
use OvhSdk\Ovh;
use Psr\Clock\ClockInterface;
use Psr\SimpleCache\CacheInterface;
use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\NullAuthenticator;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\PendingRequest;
use Tests\Support\FrozenClock;

/**
 * Build a connector backed by a Saloon mock client.
 *
 * @param  array<int|string, mixed>  $responses
 */
function ovh(
    array $responses = [],
    ?Authenticator $credentials = null,
    ?CacheInterface $cache = null,
    ?ClockInterface $clock = null,
    Region $region = Region::Eu,
): Ovh {
    $ovh = new Ovh(
        $region,
        $credentials ?? new NullAuthenticator,
        $cache,
        $clock ?? new FrozenClock(new DateTimeImmutable('@1700000000')),
    );

    return $ovh->withMockClient(new MockClient($responses));
}

/**
 * Return the last pending request of the given class sent through the connector.
 *
 * @param  class-string  $requestClass
 */
function sentRequest(Ovh $ovh, string $requestClass): PendingRequest
{
    $mockClient = $ovh->getMockClient();
    $recorded = $mockClient instanceof MockClient ? $mockClient->getRecordedResponses() : [];

    foreach (array_reverse($recorded) as $response) {
        $pendingRequest = $response->getPendingRequest();

        if ($pendingRequest->getRequest() instanceof $requestClass) {
            return $pendingRequest;
        }
    }

    throw new RuntimeException(sprintf('No %s was sent.', $requestClass));
}

/**
 * Decode a JSON fixture from tests/Fixtures.
 *
 * Named fixtureData() because Pest already defines a global fixture() returning a path.
 *
 * @return array<mixed>
 */
function fixtureData(string $name): array
{
    $json = file_get_contents(__DIR__.'/Fixtures/'.$name.'.json');

    if ($json === false) {
        throw new RuntimeException(sprintf('Fixture "%s" not found.', $name));
    }

    /** @var array<mixed> */
    return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
}

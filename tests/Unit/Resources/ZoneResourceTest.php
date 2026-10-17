<?php

declare(strict_types=1);

use OvhSdk\Data\Dns\Zone;
use OvhSdk\Requests\Dns\GetZone;
use OvhSdk\Requests\Dns\ListZones;
use OvhSdk\Requests\Dns\RefreshZone;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

it('lists zone names in a single call', function () {
    $ovh = ovh([ListZones::class => MockResponse::make(['example.com'])]);

    expect($ovh->domain()->zones()->ids())->toBe(['example.com']);
});

it('hydrates every zone', function () {
    $ovh = ovh([
        ListZones::class => MockResponse::make(['example.com', 'example.org']),
        GetZone::class => fn (PendingRequest $pending) => MockResponse::make(['name' => $pending->getRequest()->zoneName] + fixtureData('zone')),
    ]);

    expect(array_map(fn (Zone $zone) => $zone->name, $ovh->domain()->zones()->list()))->toBe(['example.com', 'example.org']);
});

it('reads a zone', function () {
    $zone = ovh([GetZone::class => MockResponse::make(fixtureData('zone'))])->domain()->zone('example.com')->get();

    expect($zone->name)->toBe('example.com')
        ->and($zone->nameServers)->toBe(['dns200.anycast.me', 'ns200.anycast.me'])
        ->and($zone->dnssecActivated)->toBeFalse();
});

it('accepts a zone that was never updated', function () {
    $zone = ovh([GetZone::class => MockResponse::make(['lastUpdate' => null] + fixtureData('zone'))])->domain()->zone('example.com')->get();

    expect($zone->lastUpdate)->toBeNull();
});

it('refreshes a zone with a bodiless POST', function () {
    $ovh = ovh([RefreshZone::class => MockResponse::make('', 204)]);
    $ovh->domain()->zone('example.com')->refresh();
    $sent = sentRequest($ovh, RefreshZone::class);

    expect($sent->getMethod())->toBe(Method::POST)
        ->and((string) $sent->getUri())->toBe('https://api.eu.ovhcloud.com/v1/domain/zone/example.com/refresh')
        ->and($sent->body())->toBeNull();
});

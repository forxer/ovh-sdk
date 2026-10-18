<?php

declare(strict_types=1);

use OvhSdk\Data\Dns\Zone;
use OvhSdk\Data\Dns\ZoneFileRecord;
use OvhSdk\Enums\RecordType;
use OvhSdk\Requests\Dns\ExportZone;
use OvhSdk\Requests\Dns\GetZone;
use OvhSdk\Requests\Dns\GetZoneStatus;
use OvhSdk\Requests\Dns\ListZoneHistory;
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

it('reads the status of a zone', function () {
    $ovh = ovh([GetZoneStatus::class => MockResponse::make(fixtureData('zone-status'))]);
    $status = $ovh->domain()->zone('example.com')->status();

    expect($status->isDeployed)->toBeTrue()
        ->and($status->errors)->toBe([])
        ->and($status->warnings)->toBe([])
        ->and($status->lastRefresh)->toEqual(new DateTimeImmutable('2026-10-08T09:28:33.023320+02:00'))
        ->and($status->lastRefresh?->format('u'))->toBe('023320')
        ->and((string) sentRequest($ovh, GetZoneStatus::class)->getUri())->toBe('https://api.eu.ovhcloud.com/v1/domain/zone/example.com/status');
});

it('reads the errors and warnings of a zone status', function () {
    $status = ovh([GetZoneStatus::class => MockResponse::make(
        ['isDeployed' => false, 'errors' => ['first error'], 'warnings' => ['first warning', 'second warning']] + fixtureData('zone-status'),
    )])->domain()->zone('example.com')->status();

    expect($status->isDeployed)->toBeFalse()
        ->and($status->errors)->toBe(['first error'])
        ->and($status->warnings)->toBe(['first warning', 'second warning']);
});

it('accepts a zone status without errors, warnings or last refresh', function () {
    $status = ovh([GetZoneStatus::class => MockResponse::make(
        array_diff_key(['errors' => null, 'warnings' => null] + fixtureData('zone-status'), ['lastRefresh' => true]),
    )])->domain()->zone('example.com')->status();

    expect($status->errors)->toBeNull()
        ->and($status->warnings)->toBeNull()
        ->and($status->lastRefresh)->toBeNull();
});

it('refuses a zone status that does not follow the schema', function (array $data, string $message) {
    $zone = ovh([GetZoneStatus::class => MockResponse::make($data)])->domain()->zone('example.com');

    expect(fn () => $zone->status())->toThrow(UnexpectedValueException::class, $message);
})->with([
    'deployment flag missing' => [array_diff_key(fixtureData('zone-status'), ['isDeployed' => true]), 'OVH response field "isDeployed" must be a boolean.'],
    'errors holding an object' => [['errors' => [['message' => 'x']]] + fixtureData('zone-status'), 'OVH response field "errors" must be a list of strings or null.'],
    'warnings of the wrong type' => [['warnings' => 'none'] + fixtureData('zone-status'), 'OVH response field "warnings" must be a list of strings or null.'],
]);

it('exports a zone as a BIND zone file', function () {
    $zoneFile = (string) file_get_contents(fixture('zone-export.txt'));
    // OVHcloud sends the zone file as a JSON string: between double quotes, with escaped line breaks.
    $body = json_encode($zoneFile, JSON_THROW_ON_ERROR);
    $ovh = ovh([ExportZone::class => MockResponse::make($body)]);

    expect($body)->toStartWith('"$TTL 3600\\n@')
        ->and($ovh->domain()->zone('example.com')->export())->toBe($zoneFile)
        ->and((string) sentRequest($ovh, ExportZone::class)->getUri())->toBe('https://api.eu.ovhcloud.com/v1/domain/zone/example.com/export');
});

it('refuses a zone export that is not a JSON string', function () {
    $zone = ovh([ExportZone::class => MockResponse::make(['$TTL 3600'])])->domain()->zone('example.com');

    expect(fn () => $zone->export())->toThrow(UnexpectedValueException::class, 'OVH zone export response is not a string.');
});

it('splits the export of a zone into records, in a single call', function () {
    $ovh = ovh([ExportZone::class => MockResponse::make(json_encode((string) file_get_contents(fixture('zone-export.txt')), JSON_THROW_ON_ERROR))]);
    $records = $ovh->domain()->zone('example.com')->exportedRecords();

    expect($records)->toHaveCount(7)
        ->and($records[0])->toEqual(new ZoneFileRecord('', null, RecordType::NS, 'dns200.anycast.me.'))
        ->and($records[6])->toEqual(new ZoneFileRecord('app', 3600, RecordType::CNAME, 'www.example.com.'));
    $ovh->getMockClient()->assertSentCount(1);
});

it('reads the history of a zone, oldest first', function () {
    $ovh = ovh([ListZoneHistory::class => MockResponse::make([
        '2026-10-08T09:28:33.070330+02:00',
        '2026-10-01T10:00:00.000001+02:00',
        '2026-10-08T07:28:33.070331+00:00',
    ])]);
    $history = $ovh->domain()->zone('example.com')->history();

    expect(array_map(fn (DateTimeImmutable $date) => $date->format('Y-m-d\TH:i:s.uP'), $history))->toBe([
        '2026-10-01T10:00:00.000001+02:00',
        '2026-10-08T09:28:33.070330+02:00',
        '2026-10-08T07:28:33.070331+00:00',
    ])
        ->and((string) sentRequest($ovh, ListZoneHistory::class)->getUri())->toBe('https://api.eu.ovhcloud.com/v1/domain/zone/example.com/history');
});

it('filters the history of a zone between two dates', function () {
    $ovh = ovh([ListZoneHistory::class => MockResponse::make([])]);
    $history = $ovh->domain()->zone('example.com')->history(
        from: new DateTimeImmutable('2026-10-01T00:00:00+02:00'),
        to: new DateTime('2026-10-08T09:28:33.070330+02:00'),
    );
    $sent = sentRequest($ovh, ListZoneHistory::class);

    expect($history)->toBe([])
        ->and($sent->query()->all())->toBe([
            'creationDate.from' => '2026-10-01T00:00:00.000000+02:00',
            'creationDate.to' => '2026-10-08T09:28:33.070330+02:00',
        ])
        ->and((string) $sent->getUri())->toBe('https://api.eu.ovhcloud.com/v1/domain/zone/example.com/history?creationDate.from=2026-10-01T00%3A00%3A00.000000%2B02%3A00&creationDate.to=2026-10-08T09%3A28%3A33.070330%2B02%3A00');
});

it('refuses a zone history that is not a list of dates', function (mixed $body, string $message) {
    $zone = ovh([ListZoneHistory::class => MockResponse::make($body)])->domain()->zone('example.com');

    expect(fn () => $zone->history())->toThrow(UnexpectedValueException::class, $message);
})->with([
    'an object' => [['date' => '2026-10-08T09:28:33+02:00'], 'OVH zone history response is not a list.'],
    'a timestamp' => [[1700000000], 'OVH zone history response holds a value that is not a string.'],
    'a value that is not a date' => [['yesterday at noon'], 'OVH date value "yesterday at noon" cannot be parsed.'],
]);

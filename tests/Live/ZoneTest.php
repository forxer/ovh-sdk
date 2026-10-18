<?php

declare(strict_types=1);

use OvhSdk\Enums\RecordType;
use Tests\Support\LiveApi;

beforeEach(function () {
    if (! LiveApi::isConfigured()) {
        $this->markTestSkipped(LiveApi::SKIP_REASON);
    }
});

it('lists the test zone among the zones of the account', function () {
    expect(LiveApi::ovh()->domain()->zones()->ids())->toContain(LiveApi::domain());
});

it('reads the test zone', function () {
    expect(LiveApi::ovh()->domain()->zone(LiveApi::domain())->get()->name)->toBe(LiveApi::domain());
});

it('reads the status of the test zone, with its last refresh', function () {
    $status = LiveApi::ovh()->domain()->zone(LiveApi::domain())->status();

    // Not in the official schema: a failure here means OVHcloud stopped sending it.
    expect($status->lastRefresh)->toBeInstanceOf(DateTimeImmutable::class);
});

it('exports the test zone as a zone file', function () {
    $zoneFile = LiveApi::ovh()->domain()->zone(LiveApi::domain())->export();

    expect($zoneFile)->toContain('IN SOA');
});

it('reads a record of the test zone without an unknown type', function () {
    $records = LiveApi::ovh()->domain()->zone(LiveApi::domain())->records();
    $ids = $records->ids();

    expect($ids)->not->toBeEmpty();

    $record = $records->get($ids[0]);

    expect($record->zone)->toBe(LiveApi::domain())
        ->and($record->type)->not->toBe(RecordType::Unknown);
});

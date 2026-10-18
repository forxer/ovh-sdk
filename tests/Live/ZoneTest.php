<?php

declare(strict_types=1);

use OvhSdk\Data\Dns\Record;
use OvhSdk\Data\Dns\Redirection;
use OvhSdk\Data\Dns\ZoneFileRecord;
use OvhSdk\Data\Dns\ZoneTask;
use OvhSdk\Enums\RecordType;
use OvhSdk\Enums\RedirectionType;
use OvhSdk\Enums\TaskFunction;
use OvhSdk\Enums\TaskStatus;
use OvhSdk\Exceptions\BadRequestException;
use OvhSdk\Requests\Dns\ListZoneTasks;
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

it('splits the export of the test zone into as many records as the record API lists', function () {
    $zone = LiveApi::ovh()->domain()->zone(LiveApi::domain());
    $exported = $zone->exportedRecords();

    expect($exported)->toHaveCount(count($zone->records()->ids()))
        ->and(array_filter($exported, fn (ZoneFileRecord $record) => $record->type === RecordType::Unknown))->toBe([]);
});

it('writes the pseudo-types of OVHcloud as TXT records in the export of the test zone', function () {
    $zone = LiveApi::ovh()->domain()->zone(LiveApi::domain());
    $count = function (array $types): array {
        $counts = array_count_values($types);
        ksort($counts);

        return $counts;
    };
    $pseudoTypes = [RecordType::DKIM, RecordType::DMARC, RecordType::SPF];

    // One call per record: the test zone is small.
    $fromApi = array_map(fn (Record $record) => in_array($record->type, $pseudoTypes, true) ? 'TXT' : $record->type->value, $zone->records()->list());
    $fromExport = array_map(fn (ZoneFileRecord $record) => $record->type->value, $zone->exportedRecords());

    expect($count($fromExport))->toBe($count($fromApi));
});

it('reads the web redirections of the test zone without an unknown type', function () {
    $redirections = LiveApi::ovh()->domain()->zone(LiveApi::domain())->redirections()->list();

    expect(array_filter($redirections, fn (Redirection $redirection) => $redirection->type === RedirectionType::Unknown))->toBe([]);
});

it('reads the history of the test zone, oldest first, and filters it', function () {
    $zone = LiveApi::ovh()->domain()->zone(LiveApi::domain());
    $history = $zone->history();
    $sorted = $history;
    usort($sorted, fn (DateTimeImmutable $a, DateTimeImmutable $b) => $a <=> $b);

    expect($history)->toBe($sorted);

    if ($history === []) {
        return;
    }

    // The bounds are sent with their microseconds: OVHcloud must accept them, and answer within them.
    $first = $history[0];
    $last = $history[count($history) - 1];
    $filtered = $zone->history(from: $first, to: $last);

    expect(array_filter($filtered, fn (DateTimeImmutable $date) => $date < $first || $date > $last))->toBe([]);
});

it('reads the tasks of the test zone without an unknown function or status', function () {
    $tasks = LiveApi::ovh()->domain()->zone(LiveApi::domain())->tasks()->list();

    expect(array_filter($tasks, fn (ZoneTask $task) => $task->function === TaskFunction::Unknown || $task->status === TaskStatus::Unknown))->toBe([]);
});

it('filters the tasks of the test zone by status', function (TaskStatus $status) {
    expect(LiveApi::ovh()->domain()->zone(LiveApi::domain())->tasks()->ids(status: $status))->toBeList()->each->toBeInt();
})->with(array_values(array_filter(
    TaskStatus::cases(),
    fn (TaskStatus $status) => $status !== TaskStatus::Unknown && $status !== TaskStatus::Problem,
)));

// tasks() refuses this filter before sending: once this test fails, OVHcloud accepts it and the refusal can go.
it('is still answered 400 by OVHcloud when filtering the tasks by the problem status', function () {
    expect(fn () => LiveApi::ovh()->send(new ListZoneTasks(LiveApi::domain(), status: TaskStatus::Problem)))
        ->toThrow(BadRequestException::class);
});

<?php

declare(strict_types=1);

use OvhSdk\Data\Dns\ZoneTask;
use OvhSdk\Enums\TaskFunction;
use OvhSdk\Enums\TaskStatus;
use OvhSdk\Exceptions\CouldNotFilterZoneTasks;
use OvhSdk\Exceptions\CouldNotReadResponse;
use OvhSdk\Exceptions\CouldNotSendUnknownCase;
use OvhSdk\Requests\Dns\GetZoneTask;
use OvhSdk\Requests\Dns\ListZoneTasks;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

beforeEach(fn () => $this->base = 'https://api.eu.ovhcloud.com/v1/domain/zone/example.com/task');

it('lists task ids with optional filters', function () {
    $ovh = ovh([MockResponse::make([42, 43]), MockResponse::make([43])]);
    $tasks = $ovh->domain()->zone('example.com')->tasks();

    expect($tasks->zoneName)->toBe('example.com')
        ->and($tasks->ids())->toBe([42, 43])
        ->and((string) sentRequest($ovh, ListZoneTasks::class)->getUri())->toBe($this->base)
        ->and($tasks->ids(function: TaskFunction::ZoneRefresh, status: TaskStatus::Todo))->toBe([43])
        ->and((string) sentRequest($ovh, ListZoneTasks::class)->getUri())->toBe($this->base.'?function=ZoneRefresh&status=todo');
});

it('hydrates tasks and keeps the order', function () {
    $ovh = ovh([
        ListZoneTasks::class => MockResponse::make([43, 42]),
        GetZoneTask::class => fn (PendingRequest $pending) => MockResponse::make(['id' => $pending->getRequest()->id] + fixtureData('zone-task')),
    ]);

    expect(array_map(fn (ZoneTask $task) => $task->id, $ovh->domain()->zone('example.com')->tasks()->list()))->toBe([43, 42]);
});

it('reads a task', function () {
    $ovh = ovh([GetZoneTask::class => MockResponse::make(fixtureData('zone-task'))]);
    $task = $ovh->domain()->zone('example.com')->tasks()->get(42);
    $sent = sentRequest($ovh, GetZoneTask::class);

    expect($task->id)->toBe(42)
        ->and($task->function)->toBe(TaskFunction::ZoneRefresh)
        ->and($task->status)->toBe(TaskStatus::Done)
        ->and($task->comment)->toBeNull()
        ->and($task->creationDate)->toEqual(new DateTimeImmutable('2026-10-08T09:28:30+02:00'))
        ->and($task->todoDate)->toEqual(new DateTimeImmutable('2026-10-08T09:28:30+02:00'))
        ->and($task->doneDate)->toEqual(new DateTimeImmutable('2026-10-08T09:28:33+02:00'))
        ->and($task->lastUpdate)->toEqual(new DateTimeImmutable('2026-10-08T09:28:33+02:00'))
        ->and($task->canAccelerate)->toBeFalse()
        ->and($task->canCancel)->toBeFalse()
        ->and($task->canRelaunch)->toBeFalse()
        ->and($sent->getMethod())->toBe(Method::GET)
        ->and((string) $sent->getUri())->toBe($this->base.'/42');
});

it('reads a pending task, with a comment and without done date', function () {
    $task = ovh([GetZoneTask::class => MockResponse::make([
        'status' => 'todo',
        'comment' => 'Waiting for the zone check',
        'doneDate' => null,
        'lastUpdate' => null,
        'canCancel' => true,
    ] + fixtureData('zone-task'))])->domain()->zone('example.com')->tasks()->get(42);

    expect($task->status)->toBe(TaskStatus::Todo)
        ->and($task->comment)->toBe('Waiting for the zone check')
        ->and($task->doneDate)->toBeNull()
        ->and($task->lastUpdate)->toBeNull()
        ->and($task->canCancel)->toBeTrue();
});

it('maps an unknown function or status to Unknown', function () {
    $task = ovh([GetZoneTask::class => MockResponse::make(['function' => 'ZoneTeleport', 'status' => 'dreaming'] + fixtureData('zone-task'))])
        ->domain()->zone('example.com')->tasks()->get(42);

    expect($task->function)->toBe(TaskFunction::Unknown)
        ->and($task->status)->toBe(TaskStatus::Unknown);
});

it('refuses to filter tasks on an unknown function or status', function (?TaskFunction $function, ?TaskStatus $status, string $message) {
    $ovh = ovh([ListZoneTasks::class => MockResponse::make([42]), GetZoneTask::class => MockResponse::make(fixtureData('zone-task'))]);
    $tasks = $ovh->domain()->zone('example.com')->tasks();

    expect(fn () => $tasks->ids($function, $status))->toThrow(CouldNotSendUnknownCase::class, $message)
        ->and(fn () => $tasks->list($function, $status))->toThrow(CouldNotSendUnknownCase::class, $message);
    $ovh->getMockClient()->assertNothingSent();
})->with([
    'function' => [TaskFunction::Unknown, null, 'OvhSdk\\Enums\\TaskFunction::Unknown cannot be sent to OVHcloud'],
    'status' => [null, TaskStatus::Unknown, 'OvhSdk\\Enums\\TaskStatus::Unknown cannot be sent to OVHcloud'],
]);

it('refuses to filter tasks on the problem status, which OVHcloud answers with a 400', function () {
    $ovh = ovh([ListZoneTasks::class => MockResponse::make([42]), GetZoneTask::class => MockResponse::make(fixtureData('zone-task'))]);
    $tasks = $ovh->domain()->zone('example.com')->tasks();
    $message = 'Tasks of zone "example.com" cannot be filtered by OvhSdk\\Enums\\TaskStatus::Problem: OVHcloud answers 400 "Invalid inputs" to status=problem';

    expect(fn () => $tasks->ids(status: TaskStatus::Problem))->toThrow(CouldNotFilterZoneTasks::class, $message)
        ->and(fn () => $tasks->list(status: TaskStatus::Problem))->toThrow(CouldNotFilterZoneTasks::class, $message);
    $ovh->getMockClient()->assertNothingSent();
});

it('reads a task whose status is problem', function () {
    $task = ovh([GetZoneTask::class => MockResponse::make(['status' => 'problem'] + fixtureData('zone-task'))])
        ->domain()->zone('example.com')->tasks()->get(42);

    expect($task->status)->toBe(TaskStatus::Problem);
});

it('refuses a task that does not follow the schema', function (array $data, string $message) {
    $tasks = ovh([GetZoneTask::class => MockResponse::make($data)])->domain()->zone('example.com')->tasks();

    expect(fn () => $tasks->get(42))->toThrow(CouldNotReadResponse::class, $message);
})->with([
    'todo date missing' => [array_diff_key(fixtureData('zone-task'), ['todoDate' => true]), 'OVH response field "todoDate" must be a string.'],
    'done date of the wrong type' => [['doneDate' => 1700000000] + fixtureData('zone-task'), 'OVH response field "doneDate" must be a string or null.'],
    'flag missing' => [array_diff_key(fixtureData('zone-task'), ['canRelaunch' => true]), 'OVH response field "canRelaunch" must be a boolean.'],
]);

it('refuses a task list that is not a list of integers', function () {
    $tasks = ovh([ListZoneTasks::class => MockResponse::make(['42'])])->domain()->zone('example.com')->tasks();

    expect(fn () => $tasks->ids())->toThrow(CouldNotReadResponse::class, 'OVH zone task list response holds a value that is not an integer.');
});

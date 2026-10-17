<?php

declare(strict_types=1);

use OvhSdk\Data\Dns\Record;
use OvhSdk\Enums\RecordType;
use OvhSdk\Requests\Dns\CreateRecord;
use OvhSdk\Requests\Dns\DeleteRecord;
use OvhSdk\Requests\Dns\GetRecord;
use OvhSdk\Requests\Dns\ListRecords;
use OvhSdk\Requests\Dns\UpdateRecord;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

beforeEach(fn () => $this->base = 'https://api.eu.ovhcloud.com/v1/domain/zone/example.com/record');

it('lists record ids with optional filters', function () {
    $ovh = ovh([MockResponse::make([42, 43]), MockResponse::make([42])]);
    $records = $ovh->domain()->zone('example.com')->records();

    expect($records->ids())->toBe([42, 43])
        ->and((string) sentRequest($ovh, ListRecords::class)->getUri())->toBe($this->base)
        ->and($records->ids(type: RecordType::A, subDomain: 'www'))->toBe([42])
        ->and((string) sentRequest($ovh, ListRecords::class)->getUri())->toBe($this->base.'?fieldType=A&subDomain=www');
});

it('hydrates records and keeps the order', function () {
    $ovh = ovh([
        ListRecords::class => MockResponse::make([43, 42]),
        GetRecord::class => fn (PendingRequest $pending) => MockResponse::make(['id' => $pending->getRequest()->id] + fixtureData('record')),
    ]);

    expect(array_map(fn (Record $record) => $record->id, $ovh->domain()->zone('example.com')->records()->list()))->toBe([43, 42]);
});

it('reads a root record without ttl', function () {
    $record = ovh([GetRecord::class => MockResponse::make(['subDomain' => null, 'ttl' => null] + fixtureData('record'))])
        ->domain()->zone('example.com')->records()->get(42);

    expect($record->subDomain)->toBeNull()
        ->and($record->ttl)->toBeNull()
        ->and($record->type)->toBe(RecordType::A)
        ->and($record->target)->toBe('203.0.113.10');
});

it('creates a record', function () {
    $ovh = ovh([CreateRecord::class => MockResponse::make(['subDomain' => 'app'] + fixtureData('record'))]);

    $record = $ovh->domain()->zone('example.com')->records()->create(type: RecordType::A, subDomain: 'app', target: '203.0.113.10', ttl: 3600);
    $sent = sentRequest($ovh, CreateRecord::class);

    expect($record->subDomain)->toBe('app')
        ->and($sent->getMethod())->toBe(Method::POST)
        ->and((string) $sent->getUri())->toBe($this->base)
        ->and($sent->body()->all())->toBe(['fieldType' => 'A', 'target' => '203.0.113.10', 'subDomain' => 'app', 'ttl' => 3600]);
});

it('omits optional fields when creating a record', function () {
    $ovh = ovh([CreateRecord::class => MockResponse::make(fixtureData('record'))]);
    $ovh->domain()->zone('example.com')->records()->create(RecordType::CNAME, 'target.example.net.');

    expect(sentRequest($ovh, CreateRecord::class)->body()->all())->toBe(['fieldType' => 'CNAME', 'target' => 'target.example.net.']);
});

it('only sends the changed fields on update', function () {
    $ovh = ovh([UpdateRecord::class => MockResponse::make('', 204)]);
    $ovh->domain()->zone('example.com')->records()->update(42, target: '203.0.113.20');
    $sent = sentRequest($ovh, UpdateRecord::class);

    expect($sent->getMethod())->toBe(Method::PUT)
        ->and((string) $sent->getUri())->toBe($this->base.'/42')
        ->and($sent->body()->all())->toBe(['target' => '203.0.113.20']);
});

it('sends an empty sub-domain and a zero ttl on update', function () {
    $ovh = ovh([UpdateRecord::class => MockResponse::make('', 204)]);
    $ovh->domain()->zone('example.com')->records()->update(42, subDomain: '', ttl: 0);

    expect(sentRequest($ovh, UpdateRecord::class)->body()->all())->toBe(['subDomain' => '', 'ttl' => 0]);
});

it('refuses an update without changes', function () {
    $ovh = ovh();

    expect(fn () => $ovh->domain()->zone('example.com')->records()->update(42))->toThrow(InvalidArgumentException::class);
    $ovh->getMockClient()->assertNothingSent();
});

it('deletes a record', function () {
    $ovh = ovh([DeleteRecord::class => MockResponse::make('', 204)]);
    $ovh->domain()->zone('example.com')->records()->delete(42);
    $sent = sentRequest($ovh, DeleteRecord::class);

    expect($sent->getMethod())->toBe(Method::DELETE)
        ->and((string) $sent->getUri())->toBe($this->base.'/42')
        ->and($sent->body())->toBeNull();
});

it('maps an unknown record type to Unknown', function () {
    $record = ovh([GetRecord::class => MockResponse::make(['fieldType' => 'XYZ'] + fixtureData('record'))])
        ->domain()->zone('example.com')->records()->get(42);

    expect($record->type)->toBe(RecordType::Unknown);
});

it('refuses to create a record of unknown type', function () {
    expect(fn () => ovh()->domain()->zone('example.com')->records()->create(RecordType::Unknown, '203.0.113.10'))
        ->toThrow(InvalidArgumentException::class);
});

it('refuses to filter records on an unknown type', function () {
    $ovh = ovh([ListRecords::class => MockResponse::make([42]), GetRecord::class => MockResponse::make(fixtureData('record'))]);
    $records = $ovh->domain()->zone('example.com')->records();

    expect(fn () => $records->ids(type: RecordType::Unknown))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $records->list(type: RecordType::Unknown))->toThrow(InvalidArgumentException::class);
    $ovh->getMockClient()->assertNothingSent();
});

it('refuses a record that does not follow the schema', function (array $data, string $message) {
    $records = ovh([GetRecord::class => MockResponse::make($data)])->domain()->zone('example.com')->records();

    expect(fn () => $records->get(42))->toThrow(UnexpectedValueException::class, $message);
})->with([
    'id of the wrong type' => [['id' => '42'] + fixtureData('record'), 'OVH response field "id" must be an integer.'],
    'target missing' => [array_diff_key(fixtureData('record'), ['target' => true]), 'OVH response field "target" must be a string.'],
]);

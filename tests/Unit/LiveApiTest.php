<?php

declare(strict_types=1);

use OvhSdk\Data\Auth\AccessRule;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\LiveApi;

it('lets the live tests read the test domain and zone', function (string $path) {
    $ovh = ovh(['*' => MockResponse::make([])]);
    $ovh->middleware()->onRequest(LiveApi::guard('example.com'));

    $ovh->get($path);

    $ovh->getMockClient()->assertSentCount(1);
})->with([
    '/auth/time',
    '/auth/currentCredential',
    '/domain',
    '/domain/example.com',
    '/domain/example.com/serviceInfos',
    '/domain/zone',
    '/domain/zone/example.com',
    '/domain/zone/example.com/status',
    '/domain/zone/example.com/export',
    '/domain/zone/example.com/record',
    '/domain/zone/example.com/record/42',
    '/domain/zone/example.com/redirection',
    '/domain/zone/example.com/redirection/42',
    '/domain/zone/example.com/history',
    '/domain/zone/example.com/task',
    '/domain/zone/example.com/task/42',
]);

it('refuses any other request before sending it', function (Closure $call) {
    $ovh = ovh(['*' => MockResponse::make([])]);
    $ovh->middleware()->onRequest(LiveApi::guard('example.com'));

    expect(fn () => $call($ovh))->toThrow(LogicException::class);
    $ovh->getMockClient()->assertNothingSent();
})->with([
    'a write' => [fn ($ovh) => $ovh->post('/domain/zone/example.com/refresh')],
    'a deletion' => [fn ($ovh) => $ovh->delete('/domain/zone/example.com/record/42')],
    'the transfer code' => [fn ($ovh) => $ovh->get('/domain/example.com/authInfo')],
    'another domain' => [fn ($ovh) => $ovh->get('/domain/example.org')],
    'another zone' => [fn ($ovh) => $ovh->get('/domain/zone/example.org/export')],
    'another route' => [fn ($ovh) => $ovh->get('/me')],
    'a restore point' => [fn ($ovh) => $ovh->get('/domain/zone/example.com/history/2026-10-08T09:28:33.070330+02:00')],
    'a restore' => [fn ($ovh) => $ovh->post('/domain/zone/example.com/history/2026-10-08T09:28:33.070330+02:00/restore')],
    'a task acceleration' => [fn ($ovh) => $ovh->post('/domain/zone/example.com/task/42/accelerate')],
    'a zone import' => [fn ($ovh) => $ovh->post('/domain/zone/example.com/import', ['zoneFile' => '$TTL 3600'])],
    'a zone reset' => [fn ($ovh) => $ovh->post('/domain/zone/example.com/reset')],
]);

it('accepts the access rules it requests for the consumer key', function () {
    $rules = array_map(fn (AccessRule $rule) => $rule->toArray(), LiveApi::accessRules('example.com'));

    expect(fn () => LiveApi::assertReadOnly($rules, 'example.com'))->not->toThrow(LogicException::class);
});

it('refuses a consumer key that is not read only', function (array $rule) {
    expect(fn () => LiveApi::assertReadOnly([$rule], 'example.com'))->toThrow(LogicException::class);
})->with([
    'any route' => [['method' => 'GET', 'path' => '/*']],
    'every domain, transfer code included' => [['method' => 'GET', 'path' => '/domain/*']],
    'the transfer code' => [['method' => 'GET', 'path' => '/domain/example.com/authInfo']],
    'a write' => [['method' => 'POST', 'path' => '/domain/zone/example.com/refresh']],
    'a deletion' => [['method' => 'DELETE', 'path' => '/domain/zone/example.com/record/*']],
    'a malformed rule' => [['method' => 'GET']],
]);

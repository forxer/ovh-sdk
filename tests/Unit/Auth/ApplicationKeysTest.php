<?php

declare(strict_types=1);

use OvhSdk\Auth\ApplicationKeys;
use OvhSdk\Auth\Signature;
use OvhSdk\Exceptions\ServerException;
use OvhSdk\Requests\Auth\GetServerTime;
use OvhSdk\Requests\Generic\GenericJsonRequest;
use OvhSdk\Requests\Generic\GenericRequest;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\ArrayCache;
use Tests\Support\FrozenClock;

// Local clock 10 s behind the server (1699999990 against 1700000000).
beforeEach(function () {
    $this->clock = new FrozenClock(new DateTimeImmutable('@1699999990'));
    $this->keys = new ApplicationKeys('app-key', 'app-secret', 'consumer-key');
});

it('signs requests with the server time', function () {
    $ovh = ovh([
        GetServerTime::class => MockResponse::make('1700000000'),
        GenericRequest::class => MockResponse::make([]),
    ], $this->keys, clock: $this->clock);

    $ovh->get('/domain/zone/example.com/record', ['fieldType' => 'A']);
    $headers = sentRequest($ovh, GenericRequest::class)->headers();

    expect($headers->get('X-Ovh-Application'))->toBe('app-key')
        ->and($headers->get('X-Ovh-Consumer'))->toBe('consumer-key')
        ->and($headers->get('X-Ovh-Timestamp'))->toBe('1700000000')
        ->and($headers->get('X-Ovh-Signature'))->toBe('$1$a3331d4d0532c46e8ef3078b1f8f6849f3a7208f');
});

it('signs exactly the URL and body that are sent', function () {
    $ovh = ovh([
        GetServerTime::class => MockResponse::make('1700000000'),
        GenericJsonRequest::class => MockResponse::make([]),
    ], $this->keys, clock: $this->clock);

    $ovh->post('/domain/zone/example.com/record', ['fieldType' => 'TXT', 'target' => 'v=spf1 https://exemple.fr/é']);
    $pending = sentRequest($ovh, GenericJsonRequest::class);
    $psr = $pending->createPsrRequest();

    expect($pending->headers()->get('X-Ovh-Signature'))->toBe(
        Signature::compute('app-secret', 'consumer-key', 'POST', (string) $psr->getUri(), (string) $psr->getBody(), 1700000000),
    );
});

it('fetches the server time once per connector', function () {
    $ovh = ovh([
        GetServerTime::class => MockResponse::make('1700000000'),
        GenericRequest::class => MockResponse::make([]),
    ], $this->keys, clock: $this->clock);

    $ovh->get('/me');
    $ovh->get('/me');

    $ovh->getMockClient()->assertSentCount(1, GetServerTime::class);
});

it('reuses the time delta from the PSR-16 cache', function () {
    $cache = new ArrayCache($this->clock);
    $cache->set('ovh-sdk.time-delta.eu', 10);
    $ovh = ovh([GenericRequest::class => MockResponse::make([])], $this->keys, $cache, $this->clock);

    $ovh->get('/me');

    $ovh->getMockClient()->assertSentCount(0, GetServerTime::class);
    expect(sentRequest($ovh, GenericRequest::class)->headers()->get('X-Ovh-Timestamp'))->toBe('1700000000');
});

it('stores the time delta in the PSR-16 cache for the next connectors', function () {
    $cache = new ArrayCache($this->clock);
    $responses = [GetServerTime::class => MockResponse::make('1700000000'), GenericRequest::class => MockResponse::make([])];

    ovh($responses, $this->keys, $cache, $this->clock)->get('/me');
    $second = ovh($responses, $this->keys, $cache, $this->clock);
    $second->get('/me');

    expect($cache->get('ovh-sdk.time-delta.eu'))->toBe(10)
        ->and(sentRequest($second, GenericRequest::class)->headers()->get('X-Ovh-Timestamp'))->toBe('1700000000');
    $second->getMockClient()->assertSentCount(0, GetServerTime::class);
});

it('never signs the server time request', function () {
    $ovh = ovh([
        GetServerTime::class => MockResponse::make('1700000000'),
        GenericRequest::class => MockResponse::make([]),
    ], $this->keys, clock: $this->clock);

    $ovh->get('/me');
    $headers = sentRequest($ovh, GetServerTime::class)->headers();

    expect($headers->get('X-Ovh-Signature'))->toBeNull()
        ->and($headers->get('X-Ovh-Application'))->toBeNull();
});

it('surfaces a failing server time call as a typed exception', function () {
    $ovh = ovh([GetServerTime::class => MockResponse::make('', 500)], $this->keys, clock: $this->clock);

    expect(fn () => $ovh->get('/me'))->toThrow(ServerException::class);
});

it('only sends the application key when there is no consumer key', function () {
    $ovh = ovh([GenericRequest::class => MockResponse::make([])], new ApplicationKeys('app-key', 'app-secret'), clock: $this->clock);

    $ovh->get('/me');
    $headers = sentRequest($ovh, GenericRequest::class)->headers();

    expect($headers->get('X-Ovh-Application'))->toBe('app-key')
        ->and($headers->get('X-Ovh-Signature'))->toBeNull();
    $ovh->getMockClient()->assertSentCount(0, GetServerTime::class);
});

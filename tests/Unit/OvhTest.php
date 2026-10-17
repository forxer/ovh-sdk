<?php

declare(strict_types=1);

use OvhSdk\Enums\Region;
use OvhSdk\Requests\Generic\GenericJsonRequest;
use OvhSdk\Requests\Generic\GenericRequest;
use Saloon\Http\Faking\MockResponse;

it('targets the official host of each region', function (Region $region, string $url) {
    expect($region->apiUrl())->toBe($url);
})->with([
    [Region::Eu, 'https://api.eu.ovhcloud.com'],
    [Region::Ca, 'https://api.ca.ovhcloud.com'],
    [Region::Us, 'https://api.us.ovhcloud.com'],
]);

it('sends generic calls to the v1 branch by default', function () {
    $ovh = ovh([MockResponse::make(['firstname' => 'Vincent'])]);

    expect($ovh->get('/me', ['foo' => 'bar'])->json('firstname'))->toBe('Vincent')
        ->and((string) sentRequest($ovh, GenericRequest::class)->getUri())
        ->toBe('https://api.eu.ovhcloud.com/v1/me?foo=bar');
});

it('keeps an explicit v2 branch', function () {
    $ovh = ovh([MockResponse::make([])]);
    $ovh->get('/v2/iam/policy');

    expect((string) sentRequest($ovh, GenericRequest::class)->getUri())
        ->toBe('https://api.eu.ovhcloud.com/v2/iam/policy');
});

it('sends a JSON body only when there is one', function () {
    $ovh = ovh([MockResponse::make([]), MockResponse::make([])]);

    $ovh->post('/domain/zone/example.com/record', ['fieldType' => 'A']);
    expect((string) sentRequest($ovh, GenericJsonRequest::class)->body())->toBe('{"fieldType":"A"}');

    $ovh->post('/domain/zone/example.com/refresh');
    expect(sentRequest($ovh, GenericRequest::class)->body())->toBeNull();
});

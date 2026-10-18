<?php

declare(strict_types=1);

use OvhSdk\Data\Dns\Redirection;
use OvhSdk\Enums\RedirectionType;
use OvhSdk\Requests\Dns\GetRedirection;
use OvhSdk\Requests\Dns\ListRedirections;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

beforeEach(fn () => $this->base = 'https://api.eu.ovhcloud.com/v1/domain/zone/example.com/redirection');

it('lists redirection ids with an optional sub-domain filter', function () {
    $ovh = ovh([MockResponse::make([42, 43]), MockResponse::make([42])]);
    $redirections = $ovh->domain()->zone('example.com')->redirections();

    expect($redirections->zoneName)->toBe('example.com')
        ->and($redirections->ids())->toBe([42, 43])
        ->and((string) sentRequest($ovh, ListRedirections::class)->getUri())->toBe($this->base)
        ->and($redirections->ids(subDomain: 'blog'))->toBe([42])
        ->and((string) sentRequest($ovh, ListRedirections::class)->getUri())->toBe($this->base.'?subDomain=blog');
});

it('hydrates redirections and keeps the order', function () {
    $ovh = ovh([
        ListRedirections::class => MockResponse::make([43, 42]),
        GetRedirection::class => fn (PendingRequest $pending) => MockResponse::make(['id' => $pending->getRequest()->id] + fixtureData('redirection')),
    ]);

    expect(array_map(fn (Redirection $redirection) => $redirection->id, $ovh->domain()->zone('example.com')->redirections()->list()))->toBe([43, 42]);
});

it('reads a visible redirection', function () {
    $ovh = ovh([GetRedirection::class => MockResponse::make(fixtureData('redirection'))]);
    $redirection = $ovh->domain()->zone('example.com')->redirections()->get(42);
    $sent = sentRequest($ovh, GetRedirection::class);

    expect($redirection->id)->toBe(42)
        ->and($redirection->zone)->toBe('example.com')
        ->and($redirection->subDomain)->toBe('blog')
        ->and($redirection->type)->toBe(RedirectionType::VisiblePermanent)
        ->and($redirection->target)->toBe('https://www.example.com/blog')
        ->and($redirection->title)->toBeNull()
        ->and($redirection->description)->toBeNull()
        ->and($redirection->keywords)->toBeNull()
        ->and($sent->getMethod())->toBe(Method::GET)
        ->and((string) $sent->getUri())->toBe($this->base.'/42');
});

it('reads an invisible redirection of the zone root, with its page details', function () {
    $redirection = ovh([GetRedirection::class => MockResponse::make([
        'subDomain' => null,
        'type' => 'invisible',
        'title' => 'Example',
        'description' => 'The example site',
        'keywords' => 'example, site',
    ] + fixtureData('redirection'))])->domain()->zone('example.com')->redirections()->get(42);

    expect($redirection->subDomain)->toBeNull()
        ->and($redirection->type)->toBe(RedirectionType::Invisible)
        ->and($redirection->title)->toBe('Example')
        ->and($redirection->description)->toBe('The example site')
        ->and($redirection->keywords)->toBe('example, site');
});

it('maps an unknown redirection type to Unknown', function () {
    $redirection = ovh([GetRedirection::class => MockResponse::make(['type' => 'teleport'] + fixtureData('redirection'))])
        ->domain()->zone('example.com')->redirections()->get(42);

    expect($redirection->type)->toBe(RedirectionType::Unknown);
});

it('refuses a redirection that does not follow the schema', function (array $data, string $message) {
    $redirections = ovh([GetRedirection::class => MockResponse::make($data)])->domain()->zone('example.com')->redirections();

    expect(fn () => $redirections->get(42))->toThrow(UnexpectedValueException::class, $message);
})->with([
    'id of the wrong type' => [['id' => '42'] + fixtureData('redirection'), 'OVH response field "id" must be an integer.'],
    'target missing' => [array_diff_key(fixtureData('redirection'), ['target' => true]), 'OVH response field "target" must be a string.'],
    'title of the wrong type' => [['title' => false] + fixtureData('redirection'), 'OVH response field "title" must be a string or null.'],
]);

it('refuses a redirection list that is not a list of integers', function (mixed $body, string $message) {
    $redirections = ovh([ListRedirections::class => MockResponse::make($body)])->domain()->zone('example.com')->redirections();

    expect(fn () => $redirections->ids())->toThrow(UnexpectedValueException::class, $message);
})->with([
    'an object' => [['id' => 42], 'OVH redirection list response is not a list.'],
    'a string id' => [['42'], 'OVH redirection list response holds a value that is not an integer.'],
]);

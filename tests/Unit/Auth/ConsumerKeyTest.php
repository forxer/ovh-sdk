<?php

declare(strict_types=1);

use OvhSdk\Auth\ApplicationKeys;
use OvhSdk\Auth\ServiceAccount;
use OvhSdk\Data\Auth\AccessRule;
use OvhSdk\Enums\CredentialState;
use OvhSdk\Enums\HttpMethod;
use OvhSdk\Exceptions\CouldNotReadResponse;
use OvhSdk\Exceptions\CouldNotRequestConsumerKey;
use OvhSdk\Exceptions\InvalidAccessRule;
use OvhSdk\Requests\Auth\RequestCredential;
use Saloon\Http\Faking\MockResponse;

it('requests a consumer key with the application key only', function () {
    $ovh = ovh([RequestCredential::class => MockResponse::make([
        'consumerKey' => 'ck-123',
        'state' => 'pendingValidation',
        'validationUrl' => 'https://api.eu.ovhcloud.com/auth/?credentialToken=abc',
    ])], new ApplicationKeys('app-key', 'app-secret'));

    $credential = $ovh->requestConsumerKey(
        [new AccessRule(HttpMethod::Get, '/domain'), AccessRule::fromString('POST:/domain/zone/*')],
        'https://example.com/back',
    );
    $sent = sentRequest($ovh, RequestCredential::class);

    expect($credential->consumerKey)->toBe('ck-123')
        ->and($credential->state)->toBe(CredentialState::PendingValidation)
        ->and($credential->validationUrl)->toBe('https://api.eu.ovhcloud.com/auth/?credentialToken=abc')
        ->and((string) $sent->getUri())->toBe('https://api.eu.ovhcloud.com/v1/auth/credential')
        ->and($sent->body()->all())->toBe([
            'accessRules' => [['method' => 'GET', 'path' => '/domain'], ['method' => 'POST', 'path' => '/domain/zone/*']],
            'redirection' => 'https://example.com/back',
        ])
        ->and($sent->headers()->get('X-Ovh-Application'))->toBe('app-key')
        ->and($sent->headers()->get('X-Ovh-Signature'))->toBeNull();
});

it('refuses to request a consumer key with a service account', function () {
    expect(fn () => ovh([], new ServiceAccount('client-id', 'client-secret'))->requestConsumerKey([AccessRule::fromString('GET:/me')]))
        ->toThrow(CouldNotRequestConsumerKey::class, 'Requesting a consumer key needs OvhSdk\\Auth\\ApplicationKeys credentials, the connector has OvhSdk\\Auth\\ServiceAccount.');
});

it('rejects malformed access rules', function (string $rule, string $message) {
    expect(fn () => AccessRule::fromString($rule))->toThrow(InvalidAccessRule::class, $message);
})->with([
    'without separator' => ['/domain', 'Access rule "/domain" must look like "METHOD:/path".'],
    'with an unknown method' => ['FETCH:/domain', 'Access rule "FETCH:/domain" has an unknown HTTP method "FETCH": use one of GET, POST, PUT, PATCH, DELETE.'],
    'with an empty path' => ['GET:', 'Access rule "GET:" has an empty path.'],
]);

it('resolves unknown enum values instead of failing', function () {
    expect(CredentialState::fromOvh('somethingNew'))->toBe(CredentialState::Unknown)
        ->and(CredentialState::fromOvh(null))->toBe(CredentialState::Unknown)
        ->and(CredentialState::fromOvh('validated'))->toBe(CredentialState::Validated);
});

it('rejects a credential response without consumer key', function () {
    $ovh = ovh([RequestCredential::class => MockResponse::make(['state' => 'pendingValidation'])], new ApplicationKeys('app-key', 'app-secret'));

    expect(fn () => $ovh->requestConsumerKey([AccessRule::fromString('GET:/me')]))
        ->toThrow(CouldNotReadResponse::class, 'OVH credential response is missing consumerKey.');
});

<?php

declare(strict_types=1);

use OvhSdk\Auth\ServiceAccount;
use OvhSdk\Enums\Region;
use OvhSdk\Exceptions\AuthenticationException;
use OvhSdk\Exceptions\CouldNotReadResponse;
use OvhSdk\Requests\Auth\GetOAuth2Token;
use OvhSdk\Requests\Auth\GetServerTime;
use OvhSdk\Requests\Generic\GenericRequest;
use Saloon\Http\Faking\MockResponse;
use Tests\Support\ArrayCache;
use Tests\Support\FrozenClock;

function tokenResponse(string $token = 'tok-1', int $expiresIn = 3599): MockResponse
{
    return MockResponse::make(['access_token' => $token, 'token_type' => 'Bearer', 'expires_in' => $expiresIn, 'scope' => 'all']);
}

it('requests a token with the client credentials flow', function () {
    $ovh = ovh([GetOAuth2Token::class => tokenResponse(), GenericRequest::class => MockResponse::make([])], new ServiceAccount('client-id', 'client-secret'));

    $ovh->get('/me');
    $token = sentRequest($ovh, GetOAuth2Token::class);

    expect((string) $token->getUri())->toBe('https://www.ovh.com/auth/oauth2/token')
        ->and($token->body()->all())->toBe(['grant_type' => 'client_credentials', 'client_id' => 'client-id', 'client_secret' => 'client-secret', 'scope' => 'all'])
        ->and($token->headers()->get('Content-Type'))->toBe('application/x-www-form-urlencoded')
        ->and(sentRequest($ovh, GenericRequest::class)->headers()->get('Authorization'))->toBe('Bearer tok-1');
});

it('uses the token endpoint of the region', function () {
    $ovh = ovh([GetOAuth2Token::class => tokenResponse(), GenericRequest::class => MockResponse::make([])], new ServiceAccount('client-id', 'client-secret'), region: Region::Ca);

    $ovh->get('/me');

    expect((string) sentRequest($ovh, GetOAuth2Token::class)->getUri())->toBe('https://ca.ovh.com/auth/oauth2/token');
});

it('reuses the token until shortly before it expires', function () {
    $clock = new FrozenClock(new DateTimeImmutable('@1700000000'));
    $ovh = ovh([GetOAuth2Token::class => tokenResponse(), GenericRequest::class => MockResponse::make([])], new ServiceAccount('client-id', 'client-secret'), clock: $clock);

    $ovh->get('/me');
    $ovh->get('/me');
    $ovh->getMockClient()->assertSentCount(1, GetOAuth2Token::class);

    $clock->advance(3540); // 3599 - 60 + 1
    $ovh->get('/me');
    $ovh->getMockClient()->assertSentCount(2, GetOAuth2Token::class);
});

it('shares the token through the PSR-16 cache', function () {
    $clock = new FrozenClock(new DateTimeImmutable('@1700000000'));
    $cache = new ArrayCache($clock);
    $responses = [GetOAuth2Token::class => tokenResponse(), GenericRequest::class => MockResponse::make([])];

    ovh($responses, new ServiceAccount('client-id', 'client-secret'), $cache, $clock)->get('/me');
    $second = ovh($responses, new ServiceAccount('client-id', 'client-secret'), $cache, $clock);
    $second->get('/me');

    $second->getMockClient()->assertSentCount(0, GetOAuth2Token::class);
    expect(sentRequest($second, GenericRequest::class)->headers()->get('Authorization'))->toBe('Bearer tok-1');
});

it('turns a refused token request into an AuthenticationException', function (int $status, array $body, string $message) {
    $ovh = ovh([GetOAuth2Token::class => MockResponse::make($body, $status)], new ServiceAccount('client-id', 'bad-secret'));

    expect(fn () => $ovh->get('/me'))->toThrow(function (AuthenticationException $e) use ($status, $message) {
        expect($e->getMessage())->toBe($message)
            ->and($e->getStatus())->toBe($status);
    });
})->with([
    'wrong credentials, as answered by OVHcloud' => [400, ['error' => 'invalid_client'], 'invalid_client'],
    'unauthorized' => [401, ['error' => 'invalid_client'], 'invalid_client'],
    'with a description' => [400, ['error' => 'invalid_client', 'error_description' => 'Client authentication failed'], 'Client authentication failed'],
]);

it('neither signs nor syncs the clock in OAuth2 mode', function () {
    $ovh = ovh([GetOAuth2Token::class => tokenResponse(), GenericRequest::class => MockResponse::make([])], new ServiceAccount('client-id', 'client-secret'));

    $ovh->get('/me');

    $ovh->getMockClient()->assertSentCount(0, GetServerTime::class);
    expect(sentRequest($ovh, GenericRequest::class)->headers()->get('X-Ovh-Signature'))->toBeNull();
});

it('refuses a token response without access token or lifetime', function (array $body, string $message) {
    $ovh = ovh([GetOAuth2Token::class => MockResponse::make($body)], new ServiceAccount('client-id', 'client-secret'));

    expect(fn () => $ovh->get('/me'))->toThrow(CouldNotReadResponse::class, $message);
})->with([
    'without access_token' => [['token_type' => 'Bearer', 'expires_in' => 3599], 'OVH OAuth2 token response is missing access_token.'],
    'without expires_in' => [['access_token' => 'tok-1', 'token_type' => 'Bearer'], 'OVH OAuth2 token response is missing expires_in.'],
]);

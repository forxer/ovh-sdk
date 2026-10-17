<?php

declare(strict_types=1);

namespace OvhSdk\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use OvhSdk\Data\Auth\AccessRule;
use OvhSdk\Data\Auth\CredentialRequest;
use OvhSdk\Ovh as Connector;
use OvhSdk\Requests\Auth\GetOAuth2Token;
use OvhSdk\Requests\Auth\GetServerTime;
use OvhSdk\Resources\DomainResource;
use Saloon\Http\Faking\Fixture;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Response;

/**
 * @method static DomainResource domain()
 * @method static Response get(string $path, array<string, mixed> $query = [])
 * @method static Response post(string $path, array<array-key, mixed> $body = [])
 * @method static Response put(string $path, array<array-key, mixed> $body = [])
 * @method static Response delete(string $path)
 * @method static CredentialRequest requestConsumerKey(list<AccessRule> $accessRules, ?string $redirection = null)
 *
 * @see Connector
 */
final class Ovh extends Facade
{
    /**
     * Fakes every call; /auth/time and the OAuth2 token are answered automatically unless $responses mocks them.
     *
     * @param  array<array-key, MockResponse|Fixture|callable>  $responses  Saloon mock responses, keyed by request class, URL or sequence index
     */
    public static function fake(array $responses = []): MockClient
    {
        $mockClient = new MockClient(array_replace([
            GetServerTime::class => MockResponse::make((string) time()),
            GetOAuth2Token::class => MockResponse::make(['access_token' => 'fake-token', 'token_type' => 'Bearer', 'expires_in' => 3600]),
        ], $responses));

        $connector = self::getFacadeRoot();
        \assert($connector instanceof Connector);
        $connector->withMockClient($mockClient);

        return $mockClient;
    }

    protected static function getFacadeAccessor(): string
    {
        return Connector::class;
    }
}

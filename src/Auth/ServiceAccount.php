<?php

declare(strict_types=1);

namespace OvhSdk\Auth;

use OvhSdk\Exceptions\CouldNotAuthenticateRequest;
use OvhSdk\Exceptions\CouldNotReadResponse;
use OvhSdk\Ovh;
use OvhSdk\Requests\Auth\GetOAuth2Token;
use Saloon\Contracts\Authenticator;
use Saloon\Http\PendingRequest;

/**
 * OAuth2 service account: sends a bearer token obtained with the client credentials flow.
 *
 * The token is kept in memory and, when the connector has one, in its PSR-16 cache.
 * It is renewed 60 seconds before it expires.
 */
final class ServiceAccount implements Authenticator
{
    private const int EXPIRY_MARGIN = 60;

    /** @var array{token: string, expires_at: int}|null */
    private ?array $token = null;

    public function __construct(
        public readonly string $clientId,
        #[\SensitiveParameter] public readonly string $clientSecret,
    ) {}

    public function set(PendingRequest $pendingRequest): void
    {
        $connector = $pendingRequest->getConnector();

        if (! $connector instanceof Ovh) {
            throw CouldNotAuthenticateRequest::outsideOvhConnector($this, $connector);
        }

        $pendingRequest->headers()->add('Authorization', 'Bearer '.$this->token($connector));
    }

    private function token(Ovh $ovh): string
    {
        $now = $ovh->clock->now()->getTimestamp();

        if ($this->token !== null && $this->token['expires_at'] > $now) {
            return $this->token['token'];
        }

        $key = 'ovh-sdk.oauth2-token.'.sha1($ovh->region->value.'|'.$this->clientId);
        $cached = $ovh->cache?->get($key);

        if (
            \is_array($cached)
            && isset($cached['token'], $cached['expires_at'])
            && \is_string($cached['token'])
            && \is_int($cached['expires_at'])
            && $cached['expires_at'] > $now
        ) {
            $this->token = ['token' => $cached['token'], 'expires_at' => $cached['expires_at']];

            return $this->token['token'];
        }

        $response = $ovh->send(new GetOAuth2Token($ovh->region->oauth2TokenUrl(), $this->clientId, $this->clientSecret));
        $accessToken = $response->json('access_token');
        $expiresIn = $response->json('expires_in');

        if (! \is_string($accessToken) || $accessToken === '') {
            throw CouldNotReadResponse::missingField('OAuth2 token', 'access_token');
        }

        if (! is_numeric($expiresIn)) {
            throw CouldNotReadResponse::missingField('OAuth2 token', 'expires_in');
        }

        $expiresAt = $now + (int) $expiresIn - self::EXPIRY_MARGIN;
        $this->token = ['token' => $accessToken, 'expires_at' => $expiresAt];

        if ($expiresAt > $now) {
            $ovh->cache?->set($key, $this->token, $expiresAt - $now);
        }

        return $accessToken;
    }
}

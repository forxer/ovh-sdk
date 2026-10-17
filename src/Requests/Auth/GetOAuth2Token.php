<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Auth;

use OvhSdk\Exceptions\AuthenticationException;
use OvhSdk\Exceptions\OvhException;
use Saloon\Contracts\Authenticator;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Auth\NullAuthenticator;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasFormBody;
use Throwable;

/**
 * Exchanges service account credentials for an access token (client credentials flow).
 *
 * The token endpoint lives outside the API host, hence the absolute URL and the base URL override.
 */
final class GetOAuth2Token extends Request implements HasBody
{
    use HasFormBody;

    public ?bool $allowBaseUrlOverride = true;

    protected Method $method = Method::POST;

    public function __construct(
        public readonly string $tokenUrl,
        public readonly string $clientId,
        #[\SensitiveParameter] public readonly string $clientSecret,
    ) {}

    public function resolveEndpoint(): string
    {
        return $this->tokenUrl;
    }

    /**
     * Wrong client credentials are answered 400 "invalid_client" (RFC 6749, section 5.2) rather than 401,
     * so a 400 is typed as an authentication failure too. A 401 already is one.
     */
    public function getRequestException(Response $response, ?Throwable $senderException): Throwable
    {
        $exception = OvhException::fromResponse($response, $senderException);

        return $response->status() === 400
            ? new AuthenticationException($response, $exception->getMessage(), 0, $senderException)
            : $exception;
    }

    /**
     * @return array<string, string>
     */
    protected function defaultBody(): array
    {
        return [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => 'all',
        ];
    }

    protected function defaultAuth(): Authenticator
    {
        return new NullAuthenticator();
    }
}

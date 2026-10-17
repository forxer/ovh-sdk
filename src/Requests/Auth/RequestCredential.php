<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Auth;

use OvhSdk\Data\Auth\AccessRule;
use OvhSdk\Data\Auth\CredentialRequest;
use OvhSdk\Requests\OvhRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Ask OVHcloud for a consumer key bound to the given access rules.
 */
final class RequestCredential extends OvhRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  list<AccessRule>  $accessRules
     */
    public function __construct(public readonly array $accessRules, public readonly ?string $redirection = null) {}

    public function createDtoFromResponse(Response $response): CredentialRequest
    {
        /** @var array<array-key, mixed> $data */
        $data = $response->json();

        return CredentialRequest::fromArray($data);
    }

    protected function path(): string
    {
        return '/auth/credential';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        $body = ['accessRules' => array_map(static fn (AccessRule $rule): array => $rule->toArray(), $this->accessRules)];

        if ($this->redirection !== null) {
            $body['redirection'] = $this->redirection;
        }

        return $body;
    }
}

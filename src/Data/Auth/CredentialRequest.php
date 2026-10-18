<?php

declare(strict_types=1);

namespace OvhSdk\Data\Auth;

use OvhSdk\Enums\CredentialState;
use OvhSdk\Exceptions\CouldNotReadResponse;

/**
 * Schema auth.ApiCredentialRequest.
 */
final readonly class CredentialRequest
{
    public function __construct(
        #[\SensitiveParameter] public string $consumerKey,
        public CredentialState $state,
        public ?string $validationUrl,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $consumerKey = $data['consumerKey'] ?? null;

        if (! \is_string($consumerKey) || $consumerKey === '') {
            throw CouldNotReadResponse::missingField('credential', 'consumerKey');
        }

        $state = $data['state'] ?? null;
        $validationUrl = $data['validationUrl'] ?? null;

        return new self(
            $consumerKey,
            CredentialState::fromOvh(\is_string($state) ? $state : null),
            \is_string($validationUrl) ? $validationUrl : null,
        );
    }
}

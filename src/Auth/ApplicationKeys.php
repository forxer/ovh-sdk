<?php

declare(strict_types=1);

namespace OvhSdk\Auth;

use OvhSdk\Exceptions\CouldNotAuthenticateRequest;
use OvhSdk\Ovh;
use Saloon\Contracts\Authenticator;
use Saloon\Http\PendingRequest;
use Stringable;

final readonly class ApplicationKeys implements Authenticator
{
    public function __construct(
        public string $applicationKey,
        #[\SensitiveParameter] public string $applicationSecret,
        #[\SensitiveParameter] public ?string $consumerKey = null,
    ) {}

    public function set(PendingRequest $pendingRequest): void
    {
        $connector = $pendingRequest->getConnector();

        if (! $connector instanceof Ovh) {
            throw CouldNotAuthenticateRequest::outsideOvhConnector($this, $connector);
        }

        $headers = $pendingRequest->headers();
        $headers->add('X-Ovh-Application', $this->applicationKey);

        if ($this->consumerKey === null) {
            return;
        }

        $timestamp = $connector->clock->now()->getTimestamp() + $connector->timeDelta();
        $body = $pendingRequest->body();

        $headers->add('X-Ovh-Consumer', $this->consumerKey);
        $headers->add('X-Ovh-Timestamp', (string) $timestamp);
        $headers->add('X-Ovh-Signature', Signature::compute(
            $this->applicationSecret,
            $this->consumerKey,
            $pendingRequest->getMethod()->value,
            (string) $pendingRequest->getUri(),
            $body instanceof Stringable ? (string) $body : '',
            $timestamp,
        ));
    }
}

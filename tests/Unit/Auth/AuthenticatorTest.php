<?php

declare(strict_types=1);

use OvhSdk\Auth\ApplicationKeys;
use OvhSdk\Auth\ServiceAccount;
use OvhSdk\Exceptions\CouldNotAuthenticateRequest;
use OvhSdk\Requests\Generic\GenericRequest;
use Saloon\Contracts\Authenticator;
use Saloon\Enums\Method;
use Saloon\Http\Connector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('only authenticates requests sent through the OVH connector', function (Authenticator $credentials, string $message) {
    $connector = new class extends Connector
    {
        public function resolveBaseUrl(): string
        {
            return 'https://example.com';
        }
    };
    $connector->authenticate($credentials)->withMockClient(new MockClient([MockResponse::make([])]));

    expect(fn () => $connector->send(new GenericRequest(Method::GET, '/me')))->toThrow(CouldNotAuthenticateRequest::class, $message);
})->with([
    'application keys' => [fn () => new ApplicationKeys('app-key', 'app-secret'), 'OvhSdk\Auth\ApplicationKeys can only authenticate requests sent through OvhSdk\Ovh, not Saloon\Http\Connector@anonymous.'],
    'service account' => [fn () => new ServiceAccount('client-id', 'client-secret'), 'OvhSdk\Auth\ServiceAccount can only authenticate requests sent through OvhSdk\Ovh, not Saloon\Http\Connector@anonymous.'],
]);

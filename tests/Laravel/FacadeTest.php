<?php

declare(strict_types=1);

use OvhSdk\Exceptions\ServerException;
use OvhSdk\Laravel\Facades\Ovh;
use OvhSdk\Requests\Auth\GetServerTime;
use OvhSdk\Requests\Domain\ListDomains;
use Saloon\Http\Faking\MockResponse;

it('fakes calls without mocking the clock sync', function () {
    $mock = Ovh::fake([ListDomains::class => MockResponse::make(['example.com'])]);

    expect(Ovh::domain()->ids())->toBe(['example.com']);
    $mock->assertSent(ListDomains::class);
});

it('fakes calls without mocking the OAuth2 token', function () {
    config(['ovh-sdk.auth' => 'service_account', 'ovh-sdk.service_account.client_id' => 'client-id', 'ovh-sdk.service_account.client_secret' => 'client-secret']);
    app()->forgetInstance(OvhSdk\Ovh::class);
    Ovh::clearResolvedInstances();

    Ovh::fake([ListDomains::class => MockResponse::make(['example.com'])]);

    expect(Ovh::domain()->ids())->toBe(['example.com']);
});

it('lets the caller override the default clock sync response', function () {
    Ovh::fake([GetServerTime::class => MockResponse::make('', 500), ListDomains::class => MockResponse::make([])]);

    expect(fn () => Ovh::domain()->ids())->toThrow(ServerException::class);
});

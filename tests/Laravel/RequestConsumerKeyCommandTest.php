<?php

declare(strict_types=1);

use OvhSdk\Data\Auth\AccessRule;
use OvhSdk\Laravel\Facades\Ovh;
use OvhSdk\Requests\Auth\RequestCredential;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;

it('requests a consumer key from the command line', function () {
    $mock = Ovh::fake([RequestCredential::class => MockResponse::make([
        'consumerKey' => 'ck-123',
        'state' => 'pendingValidation',
        'validationUrl' => 'https://api.eu.ovhcloud.com/auth/?credentialToken=abc',
    ])]);

    $this->artisan('ovh:consumer-key', ['rules' => ['GET:/domain', 'POST:/domain/zone/*']])
        ->expectsOutputToContain('ck-123')
        ->expectsOutputToContain('https://api.eu.ovhcloud.com/auth/?credentialToken=abc')
        ->expectsOutputToContain('OVH_CONSUMER_KEY')
        ->assertSuccessful();

    $mock->assertSent(fn (Request $request) => $request instanceof RequestCredential
        && array_map(fn (AccessRule $rule) => $rule->toArray(), $request->accessRules) === [['method' => 'GET', 'path' => '/domain'], ['method' => 'POST', 'path' => '/domain/zone/*']]);
});

it('fails on a malformed rule', function () {
    Ovh::fake();

    $this->artisan('ovh:consumer-key', ['rules' => ['/domain']])->assertFailed();
});

it('fails with a service account', function () {
    config(['ovh-sdk.auth' => 'service_account', 'ovh-sdk.service_account.client_id' => 'client-id', 'ovh-sdk.service_account.client_secret' => 'client-secret']);
    app()->forgetInstance(OvhSdk\Ovh::class);
    Ovh::clearResolvedInstances();
    Ovh::fake();

    $this->artisan('ovh:consumer-key', ['rules' => ['GET:/me']])->assertFailed();
});

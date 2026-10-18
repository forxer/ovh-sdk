<?php

declare(strict_types=1);

use OvhSdk\Auth\ApplicationKeys;
use OvhSdk\Auth\ServiceAccount;
use OvhSdk\Enums\Region;
use OvhSdk\Laravel\Exceptions\InvalidConfiguration;
use OvhSdk\Ovh;
use Psr\SimpleCache\CacheInterface;

function resolveOvh(array $config): Ovh
{
    config($config);
    app()->forgetInstance(Ovh::class);

    return app(Ovh::class);
}

it('builds a singleton connector from the configuration', function () {
    $ovh = resolveOvh(['ovh-sdk.region' => 'ca']);

    expect($ovh->region)->toBe(Region::Ca)
        ->and($ovh->getAuthenticator())->toBeInstanceOf(ApplicationKeys::class)
        ->and($ovh->cache)->toBeInstanceOf(CacheInterface::class)
        ->and(app(Ovh::class))->toBe($ovh);
});

it('builds a service account connector', function () {
    $ovh = resolveOvh(['ovh-sdk.auth' => 'service_account', 'ovh-sdk.service_account.client_id' => 'client-id', 'ovh-sdk.service_account.client_secret' => 'client-secret']);

    expect($ovh->getAuthenticator())->toBeInstanceOf(ServiceAccount::class);
});

it('names the environment variable when the configuration is wrong', function (array $config, string $variable) {
    expect(fn () => resolveOvh($config))->toThrow(InvalidConfiguration::class, $variable);
})->with([
    [['ovh-sdk.region' => 'mars'], 'OVH_REGION'],
    [['ovh-sdk.auth' => 'magic'], 'OVH_AUTH'],
    [['ovh-sdk.application_keys.application_key' => null], 'OVH_APPLICATION_KEY'],
    [['ovh-sdk.auth' => 'service_account', 'ovh-sdk.service_account.client_id' => null], 'OVH_CLIENT_ID'],
]);

it('publishes its configuration', function () {
    $this->artisan('vendor:publish', ['--tag' => 'ovh-sdk-config'])->assertSuccessful();

    expect(config_path('ovh-sdk.php'))->toBeFile();
    unlink(config_path('ovh-sdk.php'));
});

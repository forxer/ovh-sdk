<?php

declare(strict_types=1);

namespace OvhSdk\Laravel;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use OvhSdk\Auth\ApplicationKeys;
use OvhSdk\Auth\ServiceAccount;
use OvhSdk\Enums\Region;
use OvhSdk\Laravel\Commands\RequestConsumerKeyCommand;
use OvhSdk\Laravel\Exceptions\InvalidConfiguration;
use OvhSdk\Ovh;
use Saloon\Contracts\Authenticator;

final class OvhSdkServiceProvider extends ServiceProvider
{
    private const string CONFIG_PATH = __DIR__.'/../../config/ovh-sdk.php';

    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'ovh-sdk');

        $this->app->singleton(Ovh::class, static fn (Container $app): Ovh => self::makeConnector($app));
    }

    public function boot(): void
    {
        $this->publishes([self::CONFIG_PATH => config_path('ovh-sdk.php')], 'ovh-sdk-config');

        if ($this->app->runningInConsole()) {
            $this->commands([RequestConsumerKeyCommand::class]);
        }
    }

    /**
     * Build the connector from the configuration read at resolution time.
     *
     * @throws InvalidConfiguration when the configuration is incomplete or wrong
     */
    private static function makeConnector(Container $app): Ovh
    {
        $config = $app->make(Config::class);
        $cacheStore = $config->get('ovh-sdk.cache_store');

        return new Ovh(
            self::region($config),
            self::credentials($config),
            $app->make(CacheFactory::class)->store(\is_string($cacheStore) && $cacheStore !== '' ? $cacheStore : null),
        );
    }

    private static function region(Config $config): Region
    {
        $value = $config->get('ovh-sdk.region');
        $region = \is_string($value) ? Region::tryFrom($value) : null;

        if ($region === null) {
            throw InvalidConfiguration::unknownRegion();
        }

        return $region;
    }

    private static function credentials(Config $config): Authenticator
    {
        return match ($config->get('ovh-sdk.auth')) {
            'application_keys' => new ApplicationKeys(
                self::required($config, 'application_keys.application_key', 'OVH_APPLICATION_KEY'),
                self::required($config, 'application_keys.application_secret', 'OVH_APPLICATION_SECRET'),
                self::optional($config, 'application_keys.consumer_key'),
            ),
            'service_account' => new ServiceAccount(
                self::required($config, 'service_account.client_id', 'OVH_CLIENT_ID'),
                self::required($config, 'service_account.client_secret', 'OVH_CLIENT_SECRET'),
            ),
            default => throw InvalidConfiguration::unknownAuthMode(),
        };
    }

    private static function required(Config $config, string $key, string $variable): string
    {
        return self::optional($config, $key) ?? throw InvalidConfiguration::missingCredential($variable, $key);
    }

    private static function optional(Config $config, string $key): ?string
    {
        $value = $config->get('ovh-sdk.'.$key);

        return \is_string($value) && $value !== '' ? $value : null;
    }
}

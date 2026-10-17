<?php

declare(strict_types=1);

namespace Tests\Laravel;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use OvhSdk\Laravel\OvhSdkServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [OvhSdkServiceProvider::class];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('ovh-sdk.application_keys.application_key', 'app-key');
        $app['config']->set('ovh-sdk.application_keys.application_secret', 'app-secret');
        $app['config']->set('ovh-sdk.application_keys.consumer_key', 'consumer-key');
    }
}

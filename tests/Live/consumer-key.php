<?php

declare(strict_types=1);

/*
 * Request the read only consumer key of the live tests, restricted to OVH_LIVE_DOMAIN by LiveApi::accessRules().
 *
 * Needs OVH_APPLICATION_KEY, OVH_APPLICATION_SECRET and OVH_LIVE_DOMAIN in .env. Run it from the root of the package:
 *   php tests/Live/consumer-key.php
 * then validate the key at the printed URL and set OVH_CONSUMER_KEY in .env.
 */

require __DIR__.'/../../vendor/autoload.php';

use OvhSdk\Auth\ApplicationKeys;
use OvhSdk\Ovh;
use Tests\Support\LiveApi;

$config = LiveApi::config();

foreach (['OVH_APPLICATION_KEY', 'OVH_APPLICATION_SECRET', 'OVH_LIVE_DOMAIN'] as $name) {
    if ($config[$name] === '') {
        fwrite(STDERR, "Set {$name} in .env first.\n");

        exit(1);
    }
}

$rules = LiveApi::accessRules($config['OVH_LIVE_DOMAIN']);
$ovh = new Ovh(LiveApi::region(), new ApplicationKeys($config['OVH_APPLICATION_KEY'], $config['OVH_APPLICATION_SECRET']));
$credential = $ovh->requestConsumerKey($rules);

echo 'Access rules:'.PHP_EOL;

foreach ($rules as $rule) {
    echo '  '.$rule->method->value.' '.$rule->path.PHP_EOL;
}

echo PHP_EOL.'1. Validate the key at: '.$credential->validationUrl.PHP_EOL;
echo '2. Then set it in .env: OVH_CONSUMER_KEY='.$credential->consumerKey.PHP_EOL;

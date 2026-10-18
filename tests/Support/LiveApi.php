<?php

declare(strict_types=1);

namespace Tests\Support;

use Closure;
use Dotenv\Dotenv;
use LogicException;
use OvhSdk\Auth\ApplicationKeys;
use OvhSdk\Data\Auth\AccessRule;
use OvhSdk\Enums\Region;
use OvhSdk\Ovh;
use Saloon\Enums\Method;
use Saloon\Http\PendingRequest;

/**
 * Connector to the real OVHcloud API for the tests of tests/Live, configured by the .env file at the root of the
 * package (see .env.example), a variable of the environment taking precedence over the file.
 *
 * The live tests only read: the connector lets through the GET requests on the routes they need, and refuses to
 * run with a consumer key that can write or read the transfer code of the test domain.
 */
final class LiveApi
{
    public const string SKIP_REASON = 'Live tests: set the variables of .env.example in .env, see docs/testing.md.';

    private const array VARIABLES = ['OVH_REGION', 'OVH_APPLICATION_KEY', 'OVH_APPLICATION_SECRET', 'OVH_CONSUMER_KEY', 'OVH_LIVE_DOMAIN'];

    private static ?Ovh $ovh = null;

    /**
     * @return array<string, string>
     */
    public static function config(): array
    {
        $file = \dirname(__DIR__, 2).'/.env';
        $values = is_file($file) ? Dotenv::parse((string) file_get_contents($file)) : [];
        $config = [];

        foreach (self::VARIABLES as $name) {
            $environment = getenv($name);
            $config[$name] = \is_string($environment) && $environment !== '' ? $environment : (string) ($values[$name] ?? '');
        }

        return $config;
    }

    public static function isConfigured(): bool
    {
        $config = self::config();

        return array_all(
            ['OVH_APPLICATION_KEY', 'OVH_APPLICATION_SECRET', 'OVH_CONSUMER_KEY', 'OVH_LIVE_DOMAIN'],
            static fn (string $name): bool => $config[$name] !== '',
        );
    }

    public static function region(): Region
    {
        $region = self::config()['OVH_REGION'] ?: 'eu';

        return Region::tryFrom($region) ?? throw new LogicException(\sprintf('OVH_REGION "%s" is not a region.', $region));
    }

    public static function domain(): string
    {
        return self::config()['OVH_LIVE_DOMAIN'];
    }

    /**
     * Guarded connector, built once, after checking that the consumer key is read only.
     */
    public static function ovh(): Ovh
    {
        if (self::$ovh instanceof Ovh) {
            return self::$ovh;
        }

        $config = self::config();
        $ovh = new Ovh(self::region(), new ApplicationKeys(
            $config['OVH_APPLICATION_KEY'],
            $config['OVH_APPLICATION_SECRET'],
            $config['OVH_CONSUMER_KEY'],
        ));
        $ovh->middleware()->onRequest(self::guard($config['OVH_LIVE_DOMAIN']));

        $rules = $ovh->get('/auth/currentCredential')->json('rules');
        self::assertReadOnly(\is_array($rules) ? $rules : throw new LogicException('The rules of the consumer key cannot be read.'), $config['OVH_LIVE_DOMAIN']);

        return self::$ovh = $ovh;
    }

    /**
     * Access rules of the consumer key of the live tests, requested by tests/Live/consumer-key.php. GET /auth/time
     * needs none.
     *
     * @return list<AccessRule>
     */
    public static function accessRules(string $domain): array
    {
        return array_map(AccessRule::fromString(...), [
            'GET:/auth/currentCredential',
            'GET:/domain',
            "GET:/domain/{$domain}",
            "GET:/domain/{$domain}/serviceInfos",
            'GET:/domain/zone',
            "GET:/domain/zone/{$domain}",
            "GET:/domain/zone/{$domain}/*",
        ]);
    }

    /**
     * Request middleware refusing, before sending, anything but a GET on the routes the live tests read.
     *
     * @return Closure(PendingRequest): void
     */
    public static function guard(string $domain): Closure
    {
        $name = preg_quote(rawurlencode($domain), '#');
        $allowed = [
            '#^/v1/auth/(time|currentCredential)$#',
            '#^/v1/domain(/zone)?$#',
            "#^/v1/domain/{$name}(/serviceInfos)?$#",
            "#^/v1/domain/zone/{$name}(/status|/export|/record(/\\d+)?)?$#",
        ];

        return static function (PendingRequest $pending) use ($allowed): void {
            $path = (string) parse_url($pending->getUrl(), PHP_URL_PATH);

            if ($pending->getMethod() === Method::GET) {
                foreach ($allowed as $pattern) {
                    if (preg_match($pattern, $path) === 1) {
                        return;
                    }
                }
            }

            throw new LogicException(\sprintf('The live tests only read: %s %s refused before sending.', $pending->getMethod()->value, $path));
        };
    }

    /**
     * Refuse a consumer key holding a rule that is not a GET, or that reaches the transfer code of the test domain.
     *
     * @param  array<array-key, mixed>  $rules  the "rules" of GET /auth/currentCredential
     *
     * @throws LogicException
     */
    public static function assertReadOnly(array $rules, string $domain): void
    {
        $transferCode = '/domain/'.$domain.'/authInfo';

        foreach ($rules as $rule) {
            $method = \is_array($rule) ? $rule['method'] ?? null : null;
            $path = \is_array($rule) ? $rule['path'] ?? null : null;

            if (! \is_string($method) || ! \is_string($path)) {
                throw new LogicException('The consumer key of the live tests holds a rule that cannot be read.');
            }

            $pattern = '#^'.str_replace('\*', '.*', preg_quote($path, '#')).'$#';

            if ($method !== 'GET' || preg_match($pattern, $transferCode) === 1) {
                throw new LogicException(\sprintf(
                    'The consumer key of the live tests must be read only, without the transfer code: rule "%s %s" refused. Request one with php tests/Live/consumer-key.php.',
                    $method,
                    $path,
                ));
            }
        }
    }
}

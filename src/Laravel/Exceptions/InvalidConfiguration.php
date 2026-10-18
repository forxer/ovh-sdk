<?php

declare(strict_types=1);

namespace OvhSdk\Laravel\Exceptions;

use InvalidArgumentException;
use OvhSdk\Enums\Region;

/**
 * The ovh-sdk configuration cannot build the connector. The message names the environment variable to set, never a
 * secret value.
 */
final class InvalidConfiguration extends InvalidArgumentException
{
    public static function unknownRegion(): self
    {
        return new self(\sprintf(
            'Unknown OVHcloud region: set OVH_REGION to one of %s.',
            implode(', ', array_map(static fn (Region $case): string => '"'.$case->value.'"', Region::cases())),
        ));
    }

    public static function unknownAuthMode(): self
    {
        return new self('Unknown OVHcloud authentication mode: set OVH_AUTH to "application_keys" or "service_account".');
    }

    /**
     * @param  string  $variable  the environment variable, such as "OVH_CLIENT_ID"
     * @param  string  $key  the configuration key under "ovh-sdk.", such as "service_account.client_id"
     */
    public static function missingCredential(string $variable, string $key): self
    {
        return new self(\sprintf('Missing OVHcloud credential: set %s (configuration key "ovh-sdk.%s").', $variable, $key));
    }
}

<?php

declare(strict_types=1);

namespace OvhSdk\Exceptions;

use LogicException;
use OvhSdk\Auth\ApplicationKeys;
use Saloon\Contracts\Authenticator;

/**
 * Ovh::requestConsumerKey() is called on a connector that does not hold application keys.
 */
final class CouldNotRequestConsumerKey extends LogicException
{
    public static function withoutApplicationKeys(?Authenticator $authenticator): self
    {
        return new self(\sprintf(
            'Requesting a consumer key needs %s credentials, the connector has %s.',
            ApplicationKeys::class,
            $authenticator instanceof Authenticator ? $authenticator::class : 'none',
        ));
    }
}

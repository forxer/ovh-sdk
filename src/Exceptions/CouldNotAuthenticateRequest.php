<?php

declare(strict_types=1);

namespace OvhSdk\Exceptions;

use LogicException;
use OvhSdk\Ovh;
use Saloon\Contracts\Authenticator;
use Saloon\Http\Connector;

/**
 * An authenticator of the SDK is used with another connector than OvhSdk\Ovh.
 */
final class CouldNotAuthenticateRequest extends LogicException
{
    public static function outsideOvhConnector(Authenticator $authenticator, Connector $connector): self
    {
        return new self(\sprintf(
            '%s can only authenticate requests sent through %s, not %s.',
            $authenticator::class,
            Ovh::class,
            get_debug_type($connector),
        ));
    }
}

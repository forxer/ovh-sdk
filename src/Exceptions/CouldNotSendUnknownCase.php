<?php

declare(strict_types=1);

namespace OvhSdk\Exceptions;

use InvalidArgumentException;
use UnitEnum;

/**
 * The Unknown case of an enum read from OVHcloud is given as a filter or a field, and refused before sending.
 */
final class CouldNotSendUnknownCase extends InvalidArgumentException
{
    public static function of(UnitEnum $case): self
    {
        return new self(\sprintf(
            '%s::%s cannot be sent to OVHcloud: it only stands for a value read from OVHcloud that this version of the SDK does not know.',
            $case::class,
            $case->name,
        ));
    }
}

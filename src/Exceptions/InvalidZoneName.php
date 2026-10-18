<?php

declare(strict_types=1);

namespace OvhSdk\Exceptions;

use InvalidArgumentException;

/**
 * The zone name given to ZoneFile::parse() is empty.
 */
final class InvalidZoneName extends InvalidArgumentException
{
    public static function empty(string $zoneName): self
    {
        return new self(\sprintf('A zone file needs the name of its zone, such as "example.com", "%s" given.', $zoneName));
    }
}

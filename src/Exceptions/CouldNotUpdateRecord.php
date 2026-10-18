<?php

declare(strict_types=1);

namespace OvhSdk\Exceptions;

use InvalidArgumentException;

/**
 * A DNS record update is refused before sending.
 */
final class CouldNotUpdateRecord extends InvalidArgumentException
{
    public static function withoutChanges(string $zoneName, int $id): self
    {
        return new self(\sprintf(
            'Record %d of zone "%s" cannot be updated without any change: pass at least one of target, subDomain or ttl.',
            $id,
            $zoneName,
        ));
    }
}

<?php

declare(strict_types=1);

namespace OvhSdk\Exceptions;

use InvalidArgumentException;
use OvhSdk\Enums\TaskStatus;

/**
 * The tasks of a zone are filtered on a value that OVHcloud refuses as a filter, although its schema lists it.
 */
final class CouldNotFilterZoneTasks extends InvalidArgumentException
{
    public static function byStatus(string $zoneName, TaskStatus $status): self
    {
        return new self(\sprintf(
            'Tasks of zone "%s" cannot be filtered by %s::%s: OVHcloud answers 400 "Invalid inputs" to status=%s, although its schema lists the value. Read the tasks without this filter and keep those whose status is %2$s::%3$s.',
            $zoneName,
            $status::class,
            $status->name,
            $status->value,
        ));
    }
}

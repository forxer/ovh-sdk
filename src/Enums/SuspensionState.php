<?php

declare(strict_types=1);

namespace OvhSdk\Enums;

use OvhSdk\Enums\Concerns\ResolvesUnknownValues;

/**
 * Schema domain.SuspensionStateEnum.
 */
enum SuspensionState: string
{
    use ResolvesUnknownValues;

    case NotSuspended = 'not_suspended';
    case Suspended = 'suspended';
    case Unknown = '__unknown__';
}

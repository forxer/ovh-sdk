<?php

declare(strict_types=1);

namespace OvhSdk\Enums;

use OvhSdk\Enums\Concerns\ResolvesUnknownValues;

/**
 * Schema domain.LockStatusEnum.
 */
enum TransferLockStatus: string
{
    use ResolvesUnknownValues;

    case Locked = 'locked';
    case Locking = 'locking';
    case Unavailable = 'unavailable';
    case Unlocked = 'unlocked';
    case Unlocking = 'unlocking';
    case Unknown = '__unknown__';
}

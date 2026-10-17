<?php

declare(strict_types=1);

namespace OvhSdk\Enums;

use OvhSdk\Enums\Concerns\ResolvesUnknownValues;

/**
 * Schema service.StateEnum.
 */
enum ServiceStatus: string
{
    use ResolvesUnknownValues;

    case AutorenewInProgress = 'autorenewInProgress';
    case Expired = 'expired';
    case InCreation = 'inCreation';
    case Ok = 'ok';
    case PendingDebt = 'pendingDebt';
    case UnPaid = 'unPaid';
    case Unknown = '__unknown__';
}

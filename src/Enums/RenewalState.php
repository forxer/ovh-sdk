<?php

declare(strict_types=1);

namespace OvhSdk\Enums;

use OvhSdk\Enums\Concerns\ResolvesUnknownValues;

/**
 * Schema domain.RenewalStateEnum.
 */
enum RenewalState: string
{
    use ResolvesUnknownValues;

    case AutomaticRenew = 'automatic_renew';
    case CancellationComplete = 'cancellation_complete';
    case CancellationRequested = 'cancellation_requested';
    case ManualRenew = 'manual_renew';
    case Unpaid = 'unpaid';
    case Unknown = '__unknown__';
}

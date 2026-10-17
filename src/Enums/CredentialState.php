<?php

declare(strict_types=1);

namespace OvhSdk\Enums;

use OvhSdk\Enums\Concerns\ResolvesUnknownValues;

enum CredentialState: string
{
    use ResolvesUnknownValues;

    case Expired = 'expired';
    case PendingValidation = 'pendingValidation';
    case Refused = 'refused';
    case Validated = 'validated';
    case Unknown = '__unknown__';
}

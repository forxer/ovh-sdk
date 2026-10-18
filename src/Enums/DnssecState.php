<?php

declare(strict_types=1);

namespace OvhSdk\Enums;

use OvhSdk\Enums\Concerns\ResolvesUnknownValues;

/**
 * Schema domain.DnssecStateEnum.
 */
enum DnssecState: string
{
    use ResolvesUnknownValues;

    case Disabled = 'disabled';
    case Enabled = 'enabled';
    case NotSupported = 'not_supported';
    case Unknown = '__unknown__';
}

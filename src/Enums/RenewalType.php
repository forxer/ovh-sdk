<?php

declare(strict_types=1);

namespace OvhSdk\Enums;

use OvhSdk\Enums\Concerns\ResolvesUnknownValues;

/**
 * Schema service.RenewalTypeEnum.
 */
enum RenewalType: string
{
    use ResolvesUnknownValues;

    case AutomaticForcedProduct = 'automaticForcedProduct';
    case AutomaticV2012 = 'automaticV2012';
    case AutomaticV2014 = 'automaticV2014';
    case AutomaticV2016 = 'automaticV2016';
    case Manual = 'manual';
    case OneShot = 'oneShot';
    case Option = 'option';
    case Unknown = '__unknown__';
}

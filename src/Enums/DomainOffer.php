<?php

declare(strict_types=1);

namespace OvhSdk\Enums;

use OvhSdk\Enums\Concerns\ResolvesUnknownValues;

/**
 * Schema domain.OfferEnum.
 */
enum DomainOffer: string
{
    use ResolvesUnknownValues;

    case Diamond = 'diamond';
    case Gold = 'gold';
    case Platinum = 'platinum';
    case Unknown = '__unknown__';
}

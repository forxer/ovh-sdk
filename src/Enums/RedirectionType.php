<?php

declare(strict_types=1);

namespace OvhSdk\Enums;

use OvhSdk\Enums\Concerns\ResolvesUnknownValues;

/**
 * Schema domain.zone.RedirectionTypeEnum.
 */
enum RedirectionType: string
{
    use ResolvesUnknownValues;

    /** Redirection through an HTML frame: the browser keeps showing the redirected address. */
    case Invisible = 'invisible';

    /** Redirection by an HTTP 302 response. */
    case Visible = 'visible';

    /** Redirection by an HTTP 301 response. */
    case VisiblePermanent = 'visiblePermanent';

    case Unknown = '__unknown__';
}

<?php

declare(strict_types=1);

namespace OvhSdk\Enums;

/**
 * Schema auth.HTTPMethodEnum. Input only, so it has no Unknown case.
 */
enum HttpMethod: string
{
    case Get = 'GET';
    case Post = 'POST';
    case Put = 'PUT';
    case Patch = 'PATCH';
    case Delete = 'DELETE';
}

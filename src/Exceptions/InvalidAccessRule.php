<?php

declare(strict_types=1);

namespace OvhSdk\Exceptions;

use InvalidArgumentException;
use OvhSdk\Enums\HttpMethod;

/**
 * An access rule given to AccessRule::fromString() is not written as "METHOD:/path".
 */
final class InvalidAccessRule extends InvalidArgumentException
{
    public static function missingSeparator(string $rule): self
    {
        return new self(\sprintf('Access rule "%s" must look like "METHOD:/path".', $rule));
    }

    public static function unknownMethod(string $rule, string $method): self
    {
        return new self(\sprintf(
            'Access rule "%s" has an unknown HTTP method "%s": use one of %s.',
            $rule,
            $method,
            implode(', ', array_map(static fn (HttpMethod $case): string => $case->value, HttpMethod::cases())),
        ));
    }

    public static function emptyPath(string $rule): self
    {
        return new self(\sprintf('Access rule "%s" has an empty path.', $rule));
    }
}

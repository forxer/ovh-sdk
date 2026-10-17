<?php

declare(strict_types=1);

namespace OvhSdk\Enums\Concerns;

/**
 * For string-backed enums declaring an `Unknown` case: a value added by OVHcloud never breaks hydration.
 */
trait ResolvesUnknownValues
{
    public static function fromOvh(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Unknown;
    }
}

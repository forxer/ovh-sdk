<?php

declare(strict_types=1);

namespace OvhSdk\Support;

use DateTimeImmutable;
use Exception;
use UnexpectedValueException;

/**
 * Parse the "date" and "datetime" values of the OVHcloud schemas.
 */
final class Dates
{
    /**
     * Parse a "date" (Y-m-d, midnight in the default timezone of the process) or a "datetime" (ISO 8601 with offset).
     *
     * @throws UnexpectedValueException
     */
    public static function parse(string $value): DateTimeImmutable
    {
        // An empty string would silently become "now".
        if (trim($value) === '') {
            throw new UnexpectedValueException('OVH date value is empty.');
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Exception $exception) {
            throw new UnexpectedValueException(\sprintf('OVH date value "%s" cannot be parsed.', $value), 0, $exception);
        }
    }

    /**
     * @throws UnexpectedValueException
     */
    public static function parseNullable(?string $value): ?DateTimeImmutable
    {
        return $value === null ? null : self::parse($value);
    }
}

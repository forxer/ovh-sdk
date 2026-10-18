<?php

declare(strict_types=1);

namespace OvhSdk\Exceptions;

use Throwable;
use UnexpectedValueException;

/**
 * A successful OVHcloud response does not follow the official schema: the SDK throws rather than hydrating half a DTO.
 */
final class CouldNotReadResponse extends UnexpectedValueException
{
    /**
     * @param  string  $expected  what the field must hold, such as "a string" or "an integer or null"
     */
    public static function invalidField(string $key, string $expected): self
    {
        return new self(\sprintf('OVH response field "%s" must be %s.', $key, $expected));
    }

    /**
     * @param  string  $response  the response, such as "zone list" or "credential"
     */
    public static function missingField(string $response, string $key): self
    {
        return new self(\sprintf('OVH %s response is missing %s.', $response, $key));
    }

    /**
     * @param  string  $response  the response, such as "zone list"
     */
    public static function notAList(string $response): self
    {
        return new self(\sprintf('OVH %s response is not a list.', $response));
    }

    /**
     * @param  string  $response  the response, such as "zone list"
     * @param  string  $expected  what each value must be, such as "a string"
     */
    public static function invalidListItem(string $response, string $expected): self
    {
        return new self(\sprintf('OVH %s response holds a value that is not %s.', $response, $expected));
    }

    /**
     * @param  string  $response  the response, such as "zone export"
     */
    public static function notAString(string $response): self
    {
        return new self(\sprintf('OVH %s response is not a string.', $response));
    }

    public static function emptyDate(): self
    {
        return new self('OVH date value is empty.');
    }

    public static function unparsableDate(string $value, Throwable $previous): self
    {
        return new self(\sprintf('OVH date value "%s" cannot be parsed.', $value), 0, $previous);
    }
}

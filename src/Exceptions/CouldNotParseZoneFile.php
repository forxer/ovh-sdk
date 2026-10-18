<?php

declare(strict_types=1);

namespace OvhSdk\Exceptions;

use UnexpectedValueException;

/**
 * A zone file read by ZoneFile::parse() holds something the parser cannot understand.
 */
final class CouldNotParseZoneFile extends UnexpectedValueException
{
    /**
     * @param  int  $zoneFileLine  the line of the zone file, from 1
     */
    private function __construct(public readonly int $zoneFileLine, string $reason)
    {
        parent::__construct(\sprintf('Zone file, line %d: %s.', $zoneFileLine, $reason));
    }

    public static function closingParenthesisWithoutOpening(int $line): self
    {
        return new self($line, 'closing parenthesis without an opening one');
    }

    public static function parenthesisLeftOpen(int $line): self
    {
        return new self($line, 'parenthesis left open');
    }

    public static function unterminatedQuoteOrEscape(int $line): self
    {
        return new self($line, 'unterminated quoted string or escape');
    }

    public static function directiveWithoutValue(int $line, string $directive): self
    {
        return new self($line, \sprintf('the %s directive has no value', $directive));
    }

    public static function unsupportedDirective(int $line, string $directive): self
    {
        return new self($line, \sprintf('the %s directive is not supported', $directive));
    }

    public static function invalidTtl(int $line, string $value): self
    {
        return new self($line, \sprintf('"%s" is not a TTL', $value));
    }

    public static function defaultTtlChanged(int $line): self
    {
        return new self($line, 'a second $TTL directive changes the default TTL');
    }

    public static function recordWithoutOwner(int $line): self
    {
        return new self($line, 'a record without owner must follow another record');
    }

    public static function recordWithoutType(int $line): self
    {
        return new self($line, 'record without type');
    }

    public static function recordWithoutData(int $line, string $type): self
    {
        return new self($line, \sprintf('the %s record has no data', $type));
    }

    public static function nameOutsideZone(int $line, string $name, string $zone): self
    {
        return new self($line, \sprintf('"%s" is outside the zone "%s"', $name, $zone));
    }
}

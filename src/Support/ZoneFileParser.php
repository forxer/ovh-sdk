<?php

declare(strict_types=1);

namespace OvhSdk\Support;

use InvalidArgumentException;
use OvhSdk\Data\Dns\ZoneFile;
use OvhSdk\Data\Dns\ZoneFileRecord;
use OvhSdk\Enums\RecordType;
use UnexpectedValueException;

/**
 * Parser of the BIND zone files (RFC 1035, section 5.1), behind ZoneFile::parse().
 *
 * Supports the $TTL and $ORIGIN directives, comments, owners left blank, "@", absolute and relative names,
 * parentheses over several lines, and the TTL and class in either order. The data of a record is kept as written,
 * its fields joined by a single space.
 *
 * @internal
 */
final class ZoneFileParser
{
    /**
     * One token of a zone file. A field joins plain characters, escapes and quoted strings; a quoted string cannot
     * hold a raw line break. "invalid" catches an unterminated quoted string or a final backslash.
     */
    private const string TOKEN = '/
        (?<field>(?:[^\s"();\\\\]|\\\\.|"(?:[^"\\\\\n]|\\\\.)*")+)
        |(?<comment>;[^\n]*)
        |(?<paren>[()])
        |(?<newline>\n)
        |(?<blank>[^\S\n]+)
        |(?<invalid>.)
    /xs';

    private const array CLASSES = ['IN', 'CH', 'HS', 'CS'];

    /** Seconds per unit of a TTL written with units, such as "1h30m". */
    private const array TTL_UNITS = ['w' => 604800, 'd' => 86400, 'h' => 3600, 'm' => 60, 's' => 1];

    /** Absolute name of the zone, in lower case, with its final dot. */
    private readonly string $zone;

    /** Absolute name completing the relative names. */
    private string $origin;

    /** Absolute owner of the previous record, for the records written without one. */
    private ?string $owner = null;

    private ?int $defaultTtl = null;

    /** @var list<ZoneFileRecord> */
    private array $records = [];

    private function __construct(string $zoneName)
    {
        if (trim($zoneName, '.') === '') {
            throw new InvalidArgumentException('The zone name of a zone file cannot be empty.');
        }

        $this->origin = rtrim($zoneName, '.').'.';
        $this->zone = strtolower($this->origin);
    }

    /**
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public static function parse(string $content, string $zoneName): ZoneFile
    {
        $parser = new self($zoneName);

        foreach (self::entries($content) as [$line, $ownerless, $fields]) {
            if (! $ownerless && str_starts_with($fields[0], '$')) {
                $parser->directive($line, $fields);
            } else {
                $parser->record($line, $ownerless, $fields);
            }
        }

        return new ZoneFile($parser->defaultTtl, $parser->records);
    }

    /**
     * Splits the content into entries, one per line or per group of lines joined by parentheses, without the
     * comments, the parentheses and the blank lines.
     *
     * @return list<array{int, bool, non-empty-list<string>}> the line the entry starts on, whether it starts with a
     *                                                        blank (no owner), and its fields
     *
     * @throws UnexpectedValueException
     */
    private static function entries(string $content): array
    {
        preg_match_all(self::TOKEN, $content, $tokens, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL);

        $entries = [];
        $fields = [];
        $line = 1;
        $start = 1;
        $depth = 0;
        $ownerless = false;
        $lineStart = true;

        foreach ($tokens as $token) {
            if ($token['newline'] !== null) {
                if ($depth === 0 && $fields !== []) {
                    $entries[] = [$start, $ownerless, $fields];
                    $fields = [];
                }

                $line++;
                $lineStart = true;

                continue;
            }

            // The first character of an entry tells whether its owner is written or left blank.
            if ($lineStart && $depth === 0 && $fields === []) {
                $ownerless = $token['blank'] !== null;
                $start = $line;
            }

            $lineStart = false;

            if ($token['field'] !== null) {
                $fields[] = $token['field'];
                $line += substr_count($token['field'], "\n");
            } elseif ($token['paren'] === '(') {
                $depth++;
            } elseif ($token['paren'] === ')') {
                $depth = $depth > 0 ? $depth - 1 : throw self::invalid($line, 'closing parenthesis without an opening one');
            } elseif ($token['invalid'] !== null) {
                throw self::invalid($line, 'unterminated quoted string or escape');
            }
        }

        if ($depth > 0) {
            throw self::invalid($start, 'parenthesis left open');
        }

        if ($fields !== []) {
            $entries[] = [$start, $ownerless, $fields];
        }

        return $entries;
    }

    /**
     * @param  non-empty-list<string>  $fields
     *
     * @throws UnexpectedValueException
     */
    private function directive(int $line, array $fields): void
    {
        $value = $fields[1] ?? throw self::invalid($line, \sprintf('the %s directive has no value', $fields[0]));

        switch (strtoupper($fields[0])) {
            case '$TTL':
                $ttl = self::ttl($value) ?? throw self::invalid($line, \sprintf('"%s" is not a TTL', $value));

                // The records without a TTL of their own would otherwise follow different defaults.
                if ($this->defaultTtl !== null && $this->defaultTtl !== $ttl) {
                    throw self::invalid($line, 'a second $TTL directive changes the default TTL');
                }

                $this->defaultTtl = $ttl;

                break;

            case '$ORIGIN':
                $this->origin = $this->absolute($value);

                break;

            default:
                throw self::invalid($line, \sprintf('the %s directive is not supported', $fields[0]));
        }
    }

    /**
     * @param  non-empty-list<string>  $fields
     *
     * @throws UnexpectedValueException
     */
    private function record(int $line, bool $ownerless, array $fields): void
    {
        $owner = $ownerless
            ? $this->owner ?? throw self::invalid($line, 'a record without owner must follow another record')
            : $this->absolute(array_shift($fields));
        $this->owner = $owner;

        // The TTL and the class are optional, in either order.
        $ttl = null;
        $class = null;

        while ($fields !== []) {
            $seconds = $ttl === null ? self::ttl($fields[0]) : null;

            if ($seconds !== null) {
                $ttl = $seconds;
                array_shift($fields);
            } elseif ($class === null && \in_array(strtoupper($fields[0]), self::CLASSES, true)) {
                $class = array_shift($fields);
            } else {
                break;
            }
        }

        $type = array_shift($fields) ?? throw self::invalid($line, 'record without type');

        if ($fields === []) {
            throw self::invalid($line, \sprintf('the %s record has no data', $type));
        }

        if (strtoupper($type) === 'SOA') {
            return;
        }

        $this->records[] = new ZoneFileRecord(
            subDomain: $this->relative($line, $owner),
            ttl: $ttl,
            type: RecordType::fromOvh(strtoupper($type)),
            target: implode(' ', $fields),
        );
    }

    private function absolute(string $name): string
    {
        return match (true) {
            $name === '@' => $this->origin,
            str_ends_with($name, '.') => $name,
            default => $name.'.'.$this->origin,
        };
    }

    /**
     * @throws UnexpectedValueException
     */
    private function relative(int $line, string $absolute): string
    {
        $name = strtolower($absolute);

        return match (true) {
            $name === $this->zone => '',
            str_ends_with($name, '.'.$this->zone) => substr($absolute, 0, -\strlen($this->zone) - 1),
            default => throw self::invalid($line, \sprintf('"%s" is outside the zone "%s"', $absolute, $this->zone)),
        };
    }

    /**
     * Seconds of a TTL, written as a number of seconds or with units ("1h30m"); null when $value is not a TTL.
     */
    private static function ttl(string $value): ?int
    {
        if (preg_match('/^\d+$/', $value) === 1) {
            return (int) $value;
        }

        if (preg_match('/^(?:\d+[wdhms])+$/i', $value) !== 1) {
            return null;
        }

        preg_match_all('/(\d+)([wdhms])/i', $value, $parts, PREG_SET_ORDER);

        return array_sum(array_map(
            static fn (array $part): int => (int) $part[1] * self::TTL_UNITS[strtolower($part[2])],
            $parts,
        ));
    }

    private static function invalid(int $line, string $message): UnexpectedValueException
    {
        return new UnexpectedValueException(\sprintf('Zone file, line %d: %s.', $line, $message));
    }
}

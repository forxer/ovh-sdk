<?php

declare(strict_types=1);

namespace OvhSdk\Data\Dns;

use InvalidArgumentException;
use OvhSdk\Support\ZoneFileParser;
use UnexpectedValueException;

/**
 * Content of a BIND zone file, as zone()->export() returns it: its default TTL and its records, in the order of the
 * file. The SOA record is left out, as the record API of OVHcloud does not hold it.
 */
final readonly class ZoneFile
{
    /**
     * @param  list<ZoneFileRecord>  $records
     */
    public function __construct(
        public ?int $defaultTtl,
        public array $records,
    ) {}

    /**
     * @param  string  $content  the zone file
     * @param  string  $zoneName  the zone the file describes, its first origin
     *
     * @throws InvalidArgumentException When $zoneName is empty.
     * @throws UnexpectedValueException When $content is not a zone file the parser understands.
     */
    public static function parse(string $content, string $zoneName): self
    {
        return ZoneFileParser::parse($content, $zoneName);
    }
}

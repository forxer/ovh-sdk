<?php

declare(strict_types=1);

namespace OvhSdk\Data\Dns;

use OvhSdk\Enums\RecordType;

/**
 * A record read from a zone file by ZoneFile::parse(). A zone file holds no record id.
 */
final readonly class ZoneFileRecord
{
    public function __construct(
        public string $subDomain,
        public ?int $ttl,
        public RecordType $type,
        public string $target,
    ) {}
}

<?php

declare(strict_types=1);

namespace OvhSdk\Resources;

use DateTimeImmutable;
use DateTimeInterface;
use OvhSdk\Data\Dns\Zone;
use OvhSdk\Data\Dns\ZoneFile;
use OvhSdk\Data\Dns\ZoneFileRecord;
use OvhSdk\Data\Dns\ZoneStatus;
use OvhSdk\Exceptions\CouldNotParseZoneFile;
use OvhSdk\Requests\Dns\ExportZone;
use OvhSdk\Requests\Dns\GetZone;
use OvhSdk\Requests\Dns\GetZoneStatus;
use OvhSdk\Requests\Dns\ListZoneHistory;
use OvhSdk\Requests\Dns\RefreshZone;
use Saloon\Http\Connector;

final class ZoneResource extends Resource
{
    public function __construct(Connector $connector, public readonly string $zoneName)
    {
        parent::__construct($connector);
    }

    public function get(): Zone
    {
        $zone = $this->connector->send(new GetZone($this->zoneName))->dto();
        \assert($zone instanceof Zone);

        return $zone;
    }

    public function status(): ZoneStatus
    {
        $status = $this->connector->send(new GetZoneStatus($this->zoneName))->dto();
        \assert($status instanceof ZoneStatus);

        return $status;
    }

    /**
     * Content of the zone, as a BIND zone file, in a single call.
     */
    public function export(): string
    {
        $zoneFile = $this->connector->send(new ExportZone($this->zoneName))->dto();
        \assert(\is_string($zoneFile));

        return $zoneFile;
    }

    /**
     * Records of the zone read from its export, in a single call whatever their number. The records hold no id, and
     * the SOA record is left out; use ZoneFile::parse() on export() to read the default TTL of the zone too.
     *
     * @return list<ZoneFileRecord>
     *
     * @throws CouldNotParseZoneFile When the export is not a zone file the parser understands.
     */
    public function exportedRecords(): array
    {
        return ZoneFile::parse($this->export(), $this->zoneName)->records;
    }

    /**
     * Creation dates of the restore points of the zone, oldest first, in a single call.
     *
     * @return list<DateTimeImmutable>
     */
    public function history(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): array
    {
        /** @var list<DateTimeImmutable> */
        return $this->connector->send(new ListZoneHistory($this->zoneName, $from, $to))->dto();
    }

    /**
     * Applies the pending changes of the zone. OVHcloud answers with no content; an error is thrown by the connector.
     */
    public function refresh(): void
    {
        $this->connector->send(new RefreshZone($this->zoneName));
    }

    public function records(): RecordsResource
    {
        return new RecordsResource($this->connector, $this->zoneName);
    }

    public function redirections(): RedirectionsResource
    {
        return new RedirectionsResource($this->connector, $this->zoneName);
    }

    public function tasks(): ZoneTasksResource
    {
        return new ZoneTasksResource($this->connector, $this->zoneName);
    }
}

<?php

declare(strict_types=1);

namespace OvhSdk\Resources;

use OvhSdk\Data\Dns\Zone;
use OvhSdk\Data\Dns\ZoneStatus;
use OvhSdk\Requests\Dns\GetZone;
use OvhSdk\Requests\Dns\GetZoneStatus;
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
}

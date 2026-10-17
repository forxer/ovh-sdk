<?php

declare(strict_types=1);

namespace OvhSdk\Resources;

use OvhSdk\Data\Dns\Zone;
use OvhSdk\Requests\Dns\GetZone;
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

    /**
     * Applies the pending changes of the zone. OVHcloud answers with no content; an error is thrown by the connector.
     */
    public function refresh(): void
    {
        $this->connector->send(new RefreshZone($this->zoneName));
    }
}

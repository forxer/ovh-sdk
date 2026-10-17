<?php

declare(strict_types=1);

namespace OvhSdk\Resources;

use OvhSdk\Data\Dns\Zone;
use OvhSdk\Requests\Dns\GetZone;
use OvhSdk\Requests\Dns\ListZones;

final class ZonesResource extends Resource
{
    /**
     * Names of the DNS zones of the account, in a single call.
     *
     * @return list<string>
     */
    public function ids(): array
    {
        /** @var list<string> */
        return $this->connector->send(new ListZones())->dto();
    }

    /**
     * Every DNS zone of the account, fetched in parallel.
     *
     * @return list<Zone>
     */
    public function list(): array
    {
        /** @var list<Zone> */
        return $this->sendAll(array_map(static fn (string $zoneName): GetZone => new GetZone($zoneName), $this->ids()));
    }
}

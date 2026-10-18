<?php

declare(strict_types=1);

namespace OvhSdk\Resources;

use InvalidArgumentException;
use OvhSdk\Data\Dns\ZoneTask;
use OvhSdk\Enums\TaskFunction;
use OvhSdk\Enums\TaskStatus;
use OvhSdk\Requests\Dns\GetZoneTask;
use OvhSdk\Requests\Dns\ListZoneTasks;
use Saloon\Http\Connector;

final class ZoneTasksResource extends Resource
{
    public function __construct(Connector $connector, public readonly string $zoneName)
    {
        parent::__construct($connector);
    }

    /**
     * Identifiers of the tasks of the zone, in a single call.
     *
     * @return list<int>
     *
     * @throws InvalidArgumentException When $function or $status is the Unknown case.
     */
    public function ids(?TaskFunction $function = null, ?TaskStatus $status = null): array
    {
        if ($function === TaskFunction::Unknown || $status === TaskStatus::Unknown) {
            throw new InvalidArgumentException('TaskFunction::Unknown and TaskStatus::Unknown cannot be sent to OVHcloud.');
        }

        /** @var list<int> */
        return $this->connector->send(new ListZoneTasks($this->zoneName, $function, $status))->dto();
    }

    /**
     * Every task of the zone matching the filters, fetched in parallel.
     *
     * @return list<ZoneTask>
     *
     * @throws InvalidArgumentException When $function or $status is the Unknown case.
     */
    public function list(?TaskFunction $function = null, ?TaskStatus $status = null): array
    {
        /** @var list<ZoneTask> */
        return $this->sendAll(array_map(
            fn (int $id): GetZoneTask => new GetZoneTask($this->zoneName, $id),
            $this->ids($function, $status),
        ));
    }

    public function get(int $id): ZoneTask
    {
        $task = $this->connector->send(new GetZoneTask($this->zoneName, $id))->dto();
        \assert($task instanceof ZoneTask);

        return $task;
    }
}

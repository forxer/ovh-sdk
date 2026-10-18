<?php

declare(strict_types=1);

namespace OvhSdk\Resources;

use OvhSdk\Data\Dns\ZoneTask;
use OvhSdk\Enums\TaskFunction;
use OvhSdk\Enums\TaskStatus;
use OvhSdk\Exceptions\CouldNotFilterZoneTasks;
use OvhSdk\Exceptions\CouldNotSendUnknownCase;
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
     * @throws CouldNotSendUnknownCase When $function or $status is the Unknown case.
     * @throws CouldNotFilterZoneTasks When $status is TaskStatus::Problem.
     */
    public function ids(?TaskFunction $function = null, ?TaskStatus $status = null): array
    {
        if ($function === TaskFunction::Unknown) {
            throw CouldNotSendUnknownCase::of($function);
        }

        if ($status === TaskStatus::Unknown) {
            throw CouldNotSendUnknownCase::of($status);
        }

        // Listed by domain.TaskStatusEnum, yet answered 400 "Invalid inputs" by OVHcloud (seen on 2026-10-18, the
        // other statuses being accepted). The live suite tells when OVHcloud accepts it.
        if ($status === TaskStatus::Problem) {
            throw CouldNotFilterZoneTasks::byStatus($this->zoneName, $status);
        }

        /** @var list<int> */
        return $this->connector->send(new ListZoneTasks($this->zoneName, $function, $status))->dto();
    }

    /**
     * Every task of the zone matching the filters, fetched in parallel.
     *
     * @return list<ZoneTask>
     *
     * @throws CouldNotSendUnknownCase When $function or $status is the Unknown case.
     * @throws CouldNotFilterZoneTasks When $status is TaskStatus::Problem.
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

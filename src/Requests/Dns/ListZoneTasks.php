<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Dns;

use OvhSdk\Enums\TaskFunction;
use OvhSdk\Enums\TaskStatus;
use OvhSdk\Exceptions\CouldNotReadResponse;
use OvhSdk\Requests\OvhRequest;
use OvhSdk\Support\Json;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * Identifiers of the tasks of a DNS zone, optionally filtered by function and status.
 */
final class ListZoneTasks extends OvhRequest
{
    protected Method $method = Method::GET;

    public function __construct(
        public readonly string $zoneName,
        public readonly ?TaskFunction $function = null,
        public readonly ?TaskStatus $status = null,
    ) {}

    /**
     * @return list<int>
     *
     * @throws CouldNotReadResponse
     */
    public function createDtoFromResponse(Response $response): array
    {
        $ids = Json::list($response, 'zone task list');

        return array_map(
            static fn (mixed $id): int => \is_int($id) ? $id : throw CouldNotReadResponse::invalidListItem('zone task list', 'an integer'),
            $ids,
        );
    }

    protected function path(): string
    {
        return '/domain/zone/'.rawurlencode($this->zoneName).'/task';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        $query = [];

        if ($this->function !== null) {
            $query['function'] = $this->function->value;
        }

        if ($this->status !== null) {
            $query['status'] = $this->status->value;
        }

        return $query;
    }
}

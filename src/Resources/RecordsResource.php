<?php

declare(strict_types=1);

namespace OvhSdk\Resources;

use InvalidArgumentException;
use OvhSdk\Data\Dns\Record;
use OvhSdk\Enums\RecordType;
use OvhSdk\Requests\Dns\CreateRecord;
use OvhSdk\Requests\Dns\DeleteRecord;
use OvhSdk\Requests\Dns\GetRecord;
use OvhSdk\Requests\Dns\ListRecords;
use OvhSdk\Requests\Dns\UpdateRecord;
use Saloon\Http\Connector;

final class RecordsResource extends Resource
{
    public function __construct(Connector $connector, public readonly string $zoneName)
    {
        parent::__construct($connector);
    }

    /**
     * Identifiers of the records of the zone, in a single call.
     *
     * @return list<int>
     *
     * @throws InvalidArgumentException When $type is RecordType::Unknown.
     */
    public function ids(?RecordType $type = null, ?string $subDomain = null): array
    {
        self::assertKnownType($type);

        /** @var list<int> */
        return $this->connector->send(new ListRecords($this->zoneName, $type, $subDomain))->dto();
    }

    /**
     * Every record of the zone matching the filters, fetched in parallel.
     *
     * @return list<Record>
     *
     * @throws InvalidArgumentException When $type is RecordType::Unknown.
     */
    public function list(?RecordType $type = null, ?string $subDomain = null): array
    {
        /** @var list<Record> */
        return $this->sendAll(array_map(
            fn (int $id): GetRecord => new GetRecord($this->zoneName, $id),
            $this->ids($type, $subDomain),
        ));
    }

    public function get(int $id): Record
    {
        $record = $this->connector->send(new GetRecord($this->zoneName, $id))->dto();
        \assert($record instanceof Record);

        return $record;
    }

    /**
     * @throws InvalidArgumentException When $type is RecordType::Unknown.
     */
    public function create(RecordType $type, string $target, ?string $subDomain = null, ?int $ttl = null): Record
    {
        self::assertKnownType($type);

        $record = $this->connector->send(new CreateRecord($this->zoneName, $type, $target, $subDomain, $ttl))->dto();
        \assert($record instanceof Record);

        return $record;
    }

    /**
     * Updates the given fields only. An empty $subDomain targets the zone root and is sent as such.
     *
     * @throws InvalidArgumentException When no field is given.
     */
    public function update(int $id, ?string $target = null, ?string $subDomain = null, ?int $ttl = null): void
    {
        $changes = array_filter(
            ['target' => $target, 'subDomain' => $subDomain, 'ttl' => $ttl],
            static fn (string|int|null $value): bool => $value !== null,
        );

        if ($changes === []) {
            throw new InvalidArgumentException('A record update needs at least one of target, subDomain or ttl.');
        }

        $this->connector->send(new UpdateRecord($this->zoneName, $id, $changes));
    }

    public function delete(int $id): void
    {
        $this->connector->send(new DeleteRecord($this->zoneName, $id));
    }

    private static function assertKnownType(?RecordType $type): void
    {
        if ($type === RecordType::Unknown) {
            throw new InvalidArgumentException('RecordType::Unknown cannot be sent to OVHcloud.');
        }
    }
}

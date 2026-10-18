<?php

declare(strict_types=1);

namespace OvhSdk\Data\Dns;

use DateTimeImmutable;
use OvhSdk\Enums\TaskFunction;
use OvhSdk\Enums\TaskStatus;
use OvhSdk\Exceptions\CouldNotReadResponse;
use OvhSdk\Support\Dates;
use OvhSdk\Support\Fields;

/**
 * Schema domain.zone.Task, an operation OVHcloud runs on a DNS zone.
 */
final readonly class ZoneTask
{
    public function __construct(
        public int $id,
        public TaskFunction $function,
        public TaskStatus $status,
        public ?string $comment,
        public DateTimeImmutable $creationDate,
        public DateTimeImmutable $todoDate,
        public ?DateTimeImmutable $doneDate,
        public ?DateTimeImmutable $lastUpdate,
        public bool $canAccelerate,
        public bool $canCancel,
        public bool $canRelaunch,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     *
     * @throws CouldNotReadResponse
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Fields::int($data, 'id'),
            function: TaskFunction::fromOvh(Fields::enumValue($data, 'function')),
            status: TaskStatus::fromOvh(Fields::enumValue($data, 'status')),
            comment: Fields::nullableString($data, 'comment'),
            creationDate: Dates::parse(Fields::string($data, 'creationDate')),
            todoDate: Dates::parse(Fields::string($data, 'todoDate')),
            doneDate: Dates::parseNullable(Fields::nullableString($data, 'doneDate')),
            lastUpdate: Dates::parseNullable(Fields::nullableString($data, 'lastUpdate')),
            canAccelerate: Fields::bool($data, 'canAccelerate'),
            canCancel: Fields::bool($data, 'canCancel'),
            canRelaunch: Fields::bool($data, 'canRelaunch'),
        );
    }
}

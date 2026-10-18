<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Dns;

use DateTimeImmutable;
use DateTimeInterface;
use OvhSdk\Exceptions\CouldNotReadResponse;
use OvhSdk\Requests\OvhRequest;
use OvhSdk\Support\Dates;
use OvhSdk\Support\Json;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * Creation dates of the restore points of a DNS zone, optionally between two dates.
 */
final class ListZoneHistory extends OvhRequest
{
    /** ISO 8601 with microseconds, as OVHcloud writes the dates of the restore points. */
    private const string DATE_FORMAT = 'Y-m-d\TH:i:s.uP';

    protected Method $method = Method::GET;

    public function __construct(
        public readonly string $zoneName,
        public readonly ?DateTimeInterface $from = null,
        public readonly ?DateTimeInterface $to = null,
    ) {}

    /**
     * The dates, oldest first: OVHcloud does not guarantee any order.
     *
     * @return list<DateTimeImmutable>
     *
     * @throws CouldNotReadResponse
     */
    public function createDtoFromResponse(Response $response): array
    {
        $values = Json::list($response, 'zone history');

        $dates = array_map(
            static fn (mixed $value): DateTimeImmutable => \is_string($value)
                ? Dates::parse($value)
                : throw CouldNotReadResponse::invalidListItem('zone history', 'a string'),
            $values,
        );

        usort($dates, static fn (DateTimeImmutable $a, DateTimeImmutable $b): int => $a <=> $b);

        return $dates;
    }

    protected function path(): string
    {
        return '/domain/zone/'.rawurlencode($this->zoneName).'/history';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        $query = [];

        if ($this->from !== null) {
            $query['creationDate.from'] = $this->from->format(self::DATE_FORMAT);
        }

        if ($this->to !== null) {
            $query['creationDate.to'] = $this->to->format(self::DATE_FORMAT);
        }

        return $query;
    }
}

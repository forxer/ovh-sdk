<?php

declare(strict_types=1);

namespace OvhSdk\Data\Service;

use OvhSdk\Support\Fields;
use UnexpectedValueException;

/**
 * Schema service.RenewType.
 */
final readonly class RenewSettings
{
    public function __construct(
        public bool $automatic,
        public bool $deleteAtExpiration,
        public bool $forced,
        public ?bool $manualPayment,
        public ?int $period,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     *
     * @throws UnexpectedValueException
     */
    public static function fromArray(array $data): self
    {
        return new self(
            automatic: Fields::bool($data, 'automatic'),
            deleteAtExpiration: Fields::bool($data, 'deleteAtExpiration'),
            forced: Fields::bool($data, 'forced'),
            manualPayment: Fields::nullableBool($data, 'manualPayment'),
            period: Fields::nullableInt($data, 'period'),
        );
    }
}

<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Generic;

use OvhSdk\Requests\OvhRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Untyped call to any OVHcloud endpoint, with a JSON body.
 */
final class GenericJsonRequest extends OvhRequest implements HasBody
{
    use HasJsonBody;

    private readonly string $path;

    /** @var array<array-key, mixed> */
    private readonly array $payload;

    /**
     * @param  array<array-key, mixed>  $body
     */
    public function __construct(Method $method, string $path, array $body)
    {
        $this->method = $method;
        $this->payload = $body;
        [$this->apiVersion, $this->path] = GenericRequest::split($path);
    }

    protected function path(): string
    {
        return $this->path;
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->payload;
    }
}

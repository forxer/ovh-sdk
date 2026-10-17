<?php

declare(strict_types=1);

namespace OvhSdk\Requests\Generic;

use OvhSdk\Enums\ApiVersion;
use OvhSdk\Requests\OvhRequest;
use Saloon\Enums\Method;

/**
 * Untyped call to any OVHcloud endpoint, without a body.
 */
final class GenericRequest extends OvhRequest
{
    private readonly string $path;

    /** @var array<string, mixed> */
    private readonly array $queryParameters;

    /**
     * @param  array<string, mixed>  $query
     */
    public function __construct(Method $method, string $path, array $query = [])
    {
        $this->method = $method;
        $this->queryParameters = $query;
        [$this->apiVersion, $this->path] = self::split($path);
    }

    /**
     * Split a path into its API branch and the remaining path ("/v2/iam" gives V2 and "/iam", anything else is V1).
     *
     * @return array{ApiVersion, string}
     */
    public static function split(string $path): array
    {
        foreach (ApiVersion::cases() as $version) {
            if (str_starts_with($path, $version->prefix().'/')) {
                return [$version, substr($path, \strlen($version->prefix()))];
            }
        }

        return [ApiVersion::V1, $path];
    }

    protected function path(): string
    {
        return $this->path;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultQuery(): array
    {
        return $this->queryParameters;
    }
}

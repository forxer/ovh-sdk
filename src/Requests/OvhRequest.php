<?php

declare(strict_types=1);

namespace OvhSdk\Requests;

use OvhSdk\Enums\ApiVersion;
use Saloon\Http\Request;

abstract class OvhRequest extends Request
{
    protected ApiVersion $apiVersion = ApiVersion::V1;

    /**
     * Path of the endpoint inside its API branch, for example "/domain/zone/example.com".
     */
    abstract protected function path(): string;

    final public function resolveEndpoint(): string
    {
        return $this->apiVersion->prefix().$this->path();
    }
}

<?php

declare(strict_types=1);

namespace OvhSdk\Enums;

enum ApiVersion: string
{
    case V1 = 'v1';
    case V2 = 'v2';

    /**
     * Path prefix of this API branch, for example "/v1".
     */
    public function prefix(): string
    {
        return '/'.$this->value;
    }
}

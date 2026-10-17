<?php

declare(strict_types=1);

namespace OvhSdk\Enums;

enum Region: string
{
    case Eu = 'eu';
    case Ca = 'ca';
    case Us = 'us';

    /**
     * Base URL of the OVHcloud API for this region.
     */
    public function apiUrl(): string
    {
        return match ($this) {
            self::Eu => 'https://api.eu.ovhcloud.com',
            self::Ca => 'https://api.ca.ovhcloud.com',
            self::Us => 'https://api.us.ovhcloud.com',
        };
    }

    /**
     * OAuth2 token endpoint for service accounts in this region.
     *
     * The EU and CA endpoints are documented by OVHcloud. The US endpoint comes
     * from the American support site and is not confirmed by the official
     * European documentation.
     */
    public function oauth2TokenUrl(): string
    {
        return match ($this) {
            self::Eu => 'https://www.ovh.com/auth/oauth2/token',
            self::Ca => 'https://ca.ovh.com/auth/oauth2/token',
            self::Us => 'https://us.ovhcloud.com/auth/oauth2/token',
        };
    }
}

<?php

declare(strict_types=1);

namespace OvhSdk\Auth;

/**
 * Request signature of the OVHcloud API ("First Steps" guide).
 */
final class Signature
{
    /**
     * "$1$" followed by the SHA1 hex digest of
     * applicationSecret+consumerKey+method+url+body+timestamp, joined by "+".
     */
    public static function compute(
        #[\SensitiveParameter] string $applicationSecret,
        #[\SensitiveParameter] string $consumerKey,
        string $method,
        string $url,
        string $body,
        int $timestamp,
    ): string {
        return '$1$'.sha1(implode('+', [$applicationSecret, $consumerKey, $method, $url, $body, $timestamp]));
    }
}

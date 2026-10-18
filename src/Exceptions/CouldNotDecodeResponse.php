<?php

declare(strict_types=1);

namespace OvhSdk\Exceptions;

use JsonException;
use Saloon\Http\Response;

/**
 * The body of a successful OVHcloud response is not JSON, an empty body included, such as the page of a proxy.
 *
 * A JSON body of the wrong shape is a CouldNotReadResponse instead.
 */
final class CouldNotDecodeResponse extends CouldNotReadResponse
{
    public static function invalidJson(Response $response, JsonException $previous): self
    {
        return new self(\sprintf(
            'OVH response to %s %s is not valid JSON: %s.',
            $response->getPendingRequest()->getMethod()->value,
            $response->getPsrRequest()->getUri(),
            $previous->getMessage(),
        ), 0, $previous);
    }
}

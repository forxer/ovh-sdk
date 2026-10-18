<?php

declare(strict_types=1);

namespace OvhSdk\Support;

use JsonException;
use OvhSdk\Exceptions\CouldNotDecodeResponse;
use OvhSdk\Exceptions\CouldNotReadResponse;
use Saloon\Http\Response;

/**
 * Decoding of the body of a successful OVHcloud response, in place of Saloon's Response::json().
 *
 * Response::json() throws a bare JsonException on a body that is not JSON, a TypeError on a JSON scalar, and
 * reads an empty body as an empty list. Here, a body that is not JSON, an empty one included, throws a
 * CouldNotDecodeResponse, and JSON of the wrong shape a CouldNotReadResponse.
 *
 * @internal
 */
final class Json
{
    /**
     * Any JSON value: object, list or scalar.
     *
     * @throws CouldNotDecodeResponse
     */
    public static function decode(Response $response): mixed
    {
        try {
            return json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw CouldNotDecodeResponse::invalidJson($response, $jsonException);
        }
    }

    /**
     * A JSON object, decoded as an array. An empty one is "{}" or "[]", as PHP encodes an empty array.
     *
     * @param  string  $what  the response, such as "zone"
     * @return array<array-key, mixed>
     *
     * @throws CouldNotDecodeResponse
     * @throws CouldNotReadResponse
     */
    public static function object(Response $response, string $what): array
    {
        $data = self::decode($response);

        return \is_array($data) && ($data === [] || ! array_is_list($data)) ? $data : throw CouldNotReadResponse::notAnObject($what);
    }

    /**
     * A JSON list.
     *
     * @param  string  $what  the response, such as "zone list"
     * @return list<mixed>
     *
     * @throws CouldNotDecodeResponse
     * @throws CouldNotReadResponse
     */
    public static function list(Response $response, string $what): array
    {
        $data = self::decode($response);

        return \is_array($data) && array_is_list($data) ? $data : throw CouldNotReadResponse::notAList($what);
    }
}

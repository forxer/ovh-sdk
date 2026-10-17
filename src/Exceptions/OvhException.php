<?php

declare(strict_types=1);

namespace OvhSdk\Exceptions;

use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Response;
use Throwable;

/**
 * Base exception for every error answered by the OVH API.
 */
class OvhException extends RequestException
{
    /** The "class" field of the error body, e.g. "Client::Forbidden". */
    public readonly ?string $errorClass;

    /** The X-Ovh-QueryId response header, when present. */
    public readonly ?string $queryId;

    public function __construct(
        Response $response,
        ?string $message = null,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($response, $message, $code, $previous);

        $this->errorClass = self::readBody($response)['class'] ?? null;

        $queryId = $response->header('X-Ovh-QueryId');
        $this->queryId = \is_string($queryId) && $queryId !== '' ? $queryId : null;
    }

    public static function fromResponse(Response $response, ?Throwable $previous = null): self
    {
        $message = self::readBody($response)['message'] ?? 'HTTP '.$response->status();

        return match (true) {
            $response->status() === 400 => new BadRequestException($response, $message, 0, $previous),
            $response->status() === 401 => new AuthenticationException($response, $message, 0, $previous),
            $response->status() === 403 => new ForbiddenException($response, $message, 0, $previous),
            $response->status() === 404 => new NotFoundException($response, $message, 0, $previous),
            $response->status() === 409 => new ConflictException($response, $message, 0, $previous),
            $response->status() === 429 => new TooManyRequestsException($response, $message, 0, $previous),
            $response->status() >= 500 => new ServerException($response, $message, 0, $previous),
            default => new self($response, $message, 0, $previous),
        };
    }

    /**
     * Read the "class" and "message" fields of the error body, ignoring anything that is not a string.
     *
     * @return array{class?: string, message?: string}
     */
    private static function readBody(Response $response): array
    {
        try {
            /** @var mixed $body */
            $body = $response->json();
        } catch (Throwable) {
            return [];
        }

        if (! \is_array($body)) {
            return [];
        }

        $fields = [];

        foreach (['class', 'message'] as $key) {
            if (isset($body[$key]) && \is_string($body[$key])) {
                $fields[$key] = $body[$key];
            }
        }

        return $fields;
    }
}

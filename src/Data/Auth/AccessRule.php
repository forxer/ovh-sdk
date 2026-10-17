<?php

declare(strict_types=1);

namespace OvhSdk\Data\Auth;

use InvalidArgumentException;
use OvhSdk\Enums\HttpMethod;

final readonly class AccessRule
{
    public function __construct(public HttpMethod $method, public string $path) {}

    /**
     * Parse a rule written as "METHOD:/path", for example "GET:/domain/*".
     *
     * @throws InvalidArgumentException
     */
    public static function fromString(string $rule): self
    {
        $separator = strpos($rule, ':');

        if ($separator === false) {
            throw new InvalidArgumentException(\sprintf('Access rule "%s" must look like "METHOD:/path".', $rule));
        }

        $method = HttpMethod::tryFrom(strtoupper(substr($rule, 0, $separator)));

        if ($method === null) {
            throw new InvalidArgumentException(\sprintf('Access rule "%s" has an unknown HTTP method.', $rule));
        }

        $path = substr($rule, $separator + 1);

        if ($path === '') {
            throw new InvalidArgumentException(\sprintf('Access rule "%s" has an empty path.', $rule));
        }

        return new self($method, $path);
    }

    /**
     * @return array{method: string, path: string}
     */
    public function toArray(): array
    {
        return ['method' => $this->method->value, 'path' => $this->path];
    }
}

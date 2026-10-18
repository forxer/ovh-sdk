<?php

declare(strict_types=1);

namespace OvhSdk\Data\Auth;

use OvhSdk\Enums\HttpMethod;
use OvhSdk\Exceptions\InvalidAccessRule;

final readonly class AccessRule
{
    public function __construct(public HttpMethod $method, public string $path) {}

    /**
     * Parse a rule written as "METHOD:/path", for example "GET:/domain/*".
     *
     * @throws InvalidAccessRule
     */
    public static function fromString(string $rule): self
    {
        $separator = strpos($rule, ':');

        if ($separator === false) {
            throw InvalidAccessRule::missingSeparator($rule);
        }

        $methodName = substr($rule, 0, $separator);
        $method = HttpMethod::tryFrom(strtoupper($methodName));

        if ($method === null) {
            throw InvalidAccessRule::unknownMethod($rule, $methodName);
        }

        $path = substr($rule, $separator + 1);

        if ($path === '') {
            throw InvalidAccessRule::emptyPath($rule);
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

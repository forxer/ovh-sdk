<?php

declare(strict_types=1);

namespace OvhSdk\Exceptions;

/**
 * HTTP 429: the rate limit has been reached.
 */
final class TooManyRequestsException extends OvhException {}

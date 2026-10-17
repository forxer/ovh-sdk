<?php

declare(strict_types=1);

namespace OvhSdk\Exceptions;

/**
 * HTTP 401: credentials are missing, invalid or expired.
 */
final class AuthenticationException extends OvhException {}

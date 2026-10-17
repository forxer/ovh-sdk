<?php

declare(strict_types=1);

namespace OvhSdk\Exceptions;

/**
 * HTTP 5xx: OVH failed to process the request.
 */
final class ServerException extends OvhException {}

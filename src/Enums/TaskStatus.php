<?php

declare(strict_types=1);

namespace OvhSdk\Enums;

use OvhSdk\Enums\Concerns\ResolvesUnknownValues;

/**
 * Schema domain.TaskStatusEnum, the status of a DNS zone task.
 */
enum TaskStatus: string
{
    use ResolvesUnknownValues;

    case Cancelled = 'cancelled';
    case Doing = 'doing';
    case Done = 'done';
    case Error = 'error';
    case Problem = 'problem';
    case Todo = 'todo';
    case Unknown = '__unknown__';
}

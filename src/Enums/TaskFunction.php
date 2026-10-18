<?php

declare(strict_types=1);

namespace OvhSdk\Enums;

use OvhSdk\Enums\Concerns\ResolvesUnknownValues;

/**
 * Schema domain.TaskFunctionEnum, the operation a DNS zone task performs.
 */
enum TaskFunction: string
{
    use ResolvesUnknownValues;

    case DnsAnycastActivate = 'DnsAnycastActivate';
    case DnsAnycastDeactivate = 'DnsAnycastDeactivate';
    case DnssecDisable = 'DnssecDisable';
    case DnssecEnable = 'DnssecEnable';
    case DnssecResigning = 'DnssecResigning';
    case DnssecRollKsk = 'DnssecRollKsk';
    case DnssecRollZsk = 'DnssecRollZsk';
    case ZoneCheck = 'ZoneCheck';
    case ZoneCreate = 'ZoneCreate';
    case ZoneCut = 'ZoneCut';
    case ZoneDelete = 'ZoneDelete';
    case ZoneImport = 'ZoneImport';
    case ZoneRefresh = 'ZoneRefresh';
    case ZoneRestore = 'ZoneRestore';
    case Unknown = '__unknown__';
}

<?php

declare(strict_types=1);

namespace OvhSdk\Enums;

use OvhSdk\Enums\Concerns\ResolvesUnknownValues;

/**
 * Schema domain.zone.RecordTypeEnum.
 */
enum RecordType: string
{
    use ResolvesUnknownValues;

    case A = 'A';
    case AAAA = 'AAAA';
    case CAA = 'CAA';
    case CNAME = 'CNAME';
    case DKIM = 'DKIM';
    case DMARC = 'DMARC';
    case DNAME = 'DNAME';
    case HTTPS = 'HTTPS';
    case LOC = 'LOC';
    case MX = 'MX';
    case NAPTR = 'NAPTR';
    case NS = 'NS';
    case PTR = 'PTR';
    case RP = 'RP';
    case SPF = 'SPF';
    case SRV = 'SRV';
    case SSHFP = 'SSHFP';
    case SVCB = 'SVCB';
    case TLSA = 'TLSA';
    case TXT = 'TXT';
    case Unknown = '__unknown__';
}

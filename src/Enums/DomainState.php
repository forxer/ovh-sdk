<?php

declare(strict_types=1);

namespace OvhSdk\Enums;

use OvhSdk\Enums\Concerns\ResolvesUnknownValues;

/**
 * Schema domain.DomainStateEnum.
 */
enum DomainState: string
{
    use ResolvesUnknownValues;

    case AutorenewInProgress = 'autorenew_in_progress';
    case AutorenewRegistryInProgress = 'autorenew_registry_in_progress';
    case Deleted = 'deleted';
    case Dispute = 'dispute';
    case Expired = 'expired';
    case Ok = 'ok';
    case OutgoingTransfer = 'outgoing_transfer';
    case PendingCreate = 'pending_create';
    case PendingDelete = 'pending_delete';
    case PendingIncomingTransfer = 'pending_incoming_transfer';
    case PendingInstallation = 'pending_installation';
    case RegistrySuspended = 'registry_suspended';
    case Restorable = 'restorable';
    case TechnicalSuspended = 'technical_suspended';
    case Unknown = '__unknown__';
}

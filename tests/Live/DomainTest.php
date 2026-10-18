<?php

declare(strict_types=1);

use OvhSdk\Enums\DnssecState;
use OvhSdk\Enums\DomainOffer;
use OvhSdk\Enums\DomainState;
use OvhSdk\Enums\RenewalState;
use OvhSdk\Enums\RenewalType;
use OvhSdk\Enums\ServiceStatus;
use OvhSdk\Enums\SuspensionState;
use OvhSdk\Enums\TransferLockStatus;
use Tests\Support\LiveApi;

beforeEach(function () {
    if (! LiveApi::isConfigured()) {
        $this->markTestSkipped(LiveApi::SKIP_REASON);
    }
});

it('lists the test domain among the domains of the account', function () {
    expect(LiveApi::ovh()->domain()->ids())->toContain(LiveApi::domain());
});

it('reads the test domain without any unknown enum value', function () {
    $domain = LiveApi::ovh()->domain()->get(LiveApi::domain());

    expect($domain->domain)->toBe(LiveApi::domain())
        ->and($domain->renewalState)->not->toBe(RenewalState::Unknown)
        ->and($domain->state)->not->toBe(DomainState::Unknown)
        ->and($domain->suspensionState)->not->toBe(SuspensionState::Unknown)
        ->and($domain->transferLockStatus)->not->toBe(TransferLockStatus::Unknown)
        ->and($domain->dnssecState)->not->toBe(DnssecState::Unknown)
        ->and($domain->offer)->not->toBe(DomainOffer::Unknown);
});

it('reads the service information of the test domain without any unknown enum value', function () {
    $infos = LiveApi::ovh()->domain()->serviceInfos(LiveApi::domain());

    expect($infos->domain)->toBe(LiveApi::domain())
        ->and($infos->renewalType)->not->toBe(RenewalType::Unknown)
        ->and($infos->status)->not->toBe(ServiceStatus::Unknown);
});

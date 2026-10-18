<?php

declare(strict_types=1);

use OvhSdk\Data\Domain\Domain;
use OvhSdk\Enums\DnssecState;
use OvhSdk\Enums\DomainOffer;
use OvhSdk\Enums\DomainState;
use OvhSdk\Enums\RenewalState;
use OvhSdk\Enums\RenewalType;
use OvhSdk\Enums\ServiceStatus;
use OvhSdk\Enums\SuspensionState;
use OvhSdk\Enums\TransferLockStatus;
use OvhSdk\Exceptions\NotFoundException;
use OvhSdk\Requests\Domain\GetDomain;
use OvhSdk\Requests\Domain\GetDomainServiceInfos;
use OvhSdk\Requests\Domain\ListDomains;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;

it('lists domain names in a single call', function () {
    $ovh = ovh([ListDomains::class => MockResponse::make(['example.com', 'example.org'])]);

    expect($ovh->domain()->ids())->toBe(['example.com', 'example.org']);
    $ovh->getMockClient()->assertSentCount(1);
});

it('reads a domain', function () {
    $ovh = ovh([GetDomain::class => MockResponse::make(fixtureData('domain'))]);
    $domain = $ovh->domain()->get('example.com');

    expect($domain->domain)->toBe('example.com')
        ->and($domain->expirationDate)->toEqual(new DateTimeImmutable('2027-03-14T00:00:00+01:00'))
        ->and($domain->renewalState)->toBe(RenewalState::ManualRenew)
        ->and($domain->state)->toBe(DomainState::Ok)
        ->and($domain->suspensionState)->toBe(SuspensionState::NotSuspended)
        ->and($domain->nameServerType)->toBe('hosting')
        ->and($domain->nameServers[0]->nameServer)->toBe('dns200.anycast.me')
        ->and($domain->nameServers[0]->ipv4)->toBeNull()
        ->and((string) sentRequest($ovh, GetDomain::class)->getUri())->toBe('https://api.eu.ovhcloud.com/v1/domain/example.com');
});

it('reads the transfer lock, DNSSEC and offer of a domain', function () {
    $domain = ovh([GetDomain::class => MockResponse::make(fixtureData('domain'))])->domain()->get('example.com');

    expect($domain->transferLockStatus)->toBe(TransferLockStatus::Locked)
        ->and($domain->dnssecState)->toBe(DnssecState::Disabled)
        ->and($domain->dnssecSupported)->toBeTrue()
        ->and($domain->offer)->toBe(DomainOffer::Gold);
});

it('reads the service information of a domain', function () {
    $infos = ovh([GetDomainServiceInfos::class => MockResponse::make(fixtureData('domain-service-infos'))])
        ->domain()->serviceInfos('example.com');

    expect($infos->expiration->format('Y-m-d'))->toBe('2027-03-14')
        ->and($infos->engagedUpTo)->toBeNull()
        ->and($infos->renew->automatic)->toBeFalse()
        ->and($infos->renew->manualPayment)->toBeTrue()
        ->and($infos->renew->period)->toBeNull()
        ->and($infos->renewalType)->toBe(RenewalType::AutomaticV2016)
        ->and($infos->status)->toBe(ServiceStatus::Ok);
});

it('accepts service information without renew settings', function () {
    $infos = ovh([GetDomainServiceInfos::class => MockResponse::make(['renew' => null] + fixtureData('domain-service-infos'))])
        ->domain()->serviceInfos('example.com');

    expect($infos->renew)->toBeNull();
});

it('hydrates every domain in parallel and keeps the order', function () {
    $ovh = ovh([
        ListDomains::class => MockResponse::make(['b.example', 'a.example']),
        GetDomain::class => fn (PendingRequest $pending) => MockResponse::make(['domain' => $pending->getRequest()->domain] + fixtureData('domain')),
    ]);

    $domains = $ovh->domain()->list();

    expect(array_map(fn (Domain $domain) => $domain->domain, $domains))->toBe(['b.example', 'a.example']);
    $ovh->getMockClient()->assertSentCount(2, GetDomain::class);
});

it('fails the whole list when one domain fails', function () {
    $ovh = ovh([
        ListDomains::class => MockResponse::make(['b.example', 'a.example']),
        GetDomain::class => fn (PendingRequest $pending) => $pending->getRequest()->domain === 'a.example'
            ? MockResponse::make(['class' => 'Client::NotFound', 'message' => 'Not found'], 404)
            : MockResponse::make(fixtureData('domain')),
    ]);

    expect(fn () => $ovh->domain()->list())->toThrow(NotFoundException::class);
});

it('maps an unknown domain state to Unknown', function () {
    $domain = ovh([GetDomain::class => MockResponse::make(['state' => 'brand_new_state'] + fixtureData('domain'))])
        ->domain()->get('example.com');

    expect($domain->state)->toBe(DomainState::Unknown);
});

it('maps unknown transfer lock, DNSSEC and offer values to Unknown', function () {
    $domain = ovh([GetDomain::class => MockResponse::make(
        ['transferLockStatus' => 'frozen', 'dnssecState' => 'pending', 'offer' => 'silver'] + fixtureData('domain'),
    )])->domain()->get('example.com');

    expect($domain->transferLockStatus)->toBe(TransferLockStatus::Unknown)
        ->and($domain->dnssecState)->toBe(DnssecState::Unknown)
        ->and($domain->offer)->toBe(DomainOffer::Unknown);
});

it('refuses a domain without the DNSSEC support flag', function () {
    $domains = ovh([GetDomain::class => MockResponse::make(array_diff_key(fixtureData('domain'), ['dnssecSupported' => true]))])->domain();

    expect(fn () => $domains->get('example.com'))
        ->toThrow(UnexpectedValueException::class, 'OVH response field "dnssecSupported" must be a boolean.');
});

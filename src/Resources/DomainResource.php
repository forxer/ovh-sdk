<?php

declare(strict_types=1);

namespace OvhSdk\Resources;

use OvhSdk\Data\Domain\Domain;
use OvhSdk\Data\Service\ServiceInfos;
use OvhSdk\Requests\Domain\GetDomain;
use OvhSdk\Requests\Domain\GetDomainServiceInfos;
use OvhSdk\Requests\Domain\ListDomains;

final class DomainResource extends Resource
{
    /**
     * Names of the domains of the account, in a single call.
     *
     * @return list<string>
     */
    public function ids(): array
    {
        /** @var list<string> */
        return $this->connector->send(new ListDomains)->dto();
    }

    /**
     * Every domain of the account, fetched in parallel.
     *
     * @return list<Domain>
     */
    public function list(): array
    {
        /** @var list<Domain> */
        return $this->sendAll(array_map(static fn (string $domain): GetDomain => new GetDomain($domain), $this->ids()));
    }

    public function get(string $domain): Domain
    {
        $result = $this->connector->send(new GetDomain($domain))->dto();
        \assert($result instanceof Domain);

        return $result;
    }

    public function serviceInfos(string $domain): ServiceInfos
    {
        $infos = $this->connector->send(new GetDomainServiceInfos($domain))->dto();
        \assert($infos instanceof ServiceInfos);

        return $infos;
    }
}

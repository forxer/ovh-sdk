<?php

declare(strict_types=1);

namespace OvhSdk\Resources;

use OvhSdk\Data\Dns\Redirection;
use OvhSdk\Requests\Dns\GetRedirection;
use OvhSdk\Requests\Dns\ListRedirections;
use Saloon\Http\Connector;

final class RedirectionsResource extends Resource
{
    public function __construct(Connector $connector, public readonly string $zoneName)
    {
        parent::__construct($connector);
    }

    /**
     * Identifiers of the web redirections of the zone, in a single call.
     *
     * @return list<int>
     */
    public function ids(?string $subDomain = null): array
    {
        /** @var list<int> */
        return $this->connector->send(new ListRedirections($this->zoneName, $subDomain))->dto();
    }

    /**
     * Every web redirection of the zone matching the filter, fetched in parallel.
     *
     * @return list<Redirection>
     */
    public function list(?string $subDomain = null): array
    {
        /** @var list<Redirection> */
        return $this->sendAll(array_map(
            fn (int $id): GetRedirection => new GetRedirection($this->zoneName, $id),
            $this->ids($subDomain),
        ));
    }

    public function get(int $id): Redirection
    {
        $redirection = $this->connector->send(new GetRedirection($this->zoneName, $id))->dto();
        \assert($redirection instanceof Redirection);

        return $redirection;
    }
}

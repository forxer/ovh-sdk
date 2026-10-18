<?php

declare(strict_types=1);

namespace OvhSdk\Laravel\Commands;

use Illuminate\Console\Command;
use OvhSdk\Data\Auth\AccessRule;
use OvhSdk\Exceptions\CouldNotRequestConsumerKey;
use OvhSdk\Exceptions\InvalidAccessRule;
use OvhSdk\Ovh;

final class RequestConsumerKeyCommand extends Command
{
    protected $signature = 'ovh:consumer-key
        {rules* : Access rules as METHOD:/path, e.g. GET:/domain/*}
        {--redirect= : URL to redirect to after validation}';

    protected $description = 'Request an OVHcloud consumer key bound to the given access rules';

    public function handle(Ovh $ovh): int
    {
        try {
            $credential = $ovh->requestConsumerKey($this->accessRules(), $this->redirection());
        } catch (InvalidAccessRule|CouldNotRequestConsumerKey $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Consumer key requested.');

        if ($credential->validationUrl === null) {
            $this->components->warn(\sprintf('OVHcloud returned no validation URL (state: %s).', $credential->state->value));
        } else {
            $this->line('  Open this URL to validate the access rules:');
            $this->line('  '.$credential->validationUrl);
            $this->newLine();
        }

        $this->line('  Once validated, set OVH_CONSUMER_KEY in your .env file:');
        $this->line('  OVH_CONSUMER_KEY='.$credential->consumerKey);

        return self::SUCCESS;
    }

    /**
     * @return list<AccessRule>
     *
     * @throws InvalidAccessRule
     */
    private function accessRules(): array
    {
        /** @var list<string> $rules an array argument always holds strings */
        $rules = $this->argument('rules');

        return array_map(AccessRule::fromString(...), $rules);
    }

    private function redirection(): ?string
    {
        $redirection = $this->option('redirect');

        return \is_string($redirection) && $redirection !== '' ? $redirection : null;
    }
}

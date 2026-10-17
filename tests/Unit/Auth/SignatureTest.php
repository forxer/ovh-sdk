<?php

declare(strict_types=1);

use OvhSdk\Auth\Signature;

it('follows the official signature formula', function () {
    expect(Signature::compute('app-secret', 'consumer-key', 'GET', 'https://api.eu.ovhcloud.com/v1/domain/zone/example.com/record?fieldType=A', '', 1700000000))
        ->toBe('$1$a3331d4d0532c46e8ef3078b1f8f6849f3a7208f')
        ->and(Signature::compute('app-secret', 'consumer-key', 'POST', 'https://api.eu.ovhcloud.com/v1/domain/zone/example.com/record', '{"fieldType":"A","target":"203.0.113.10"}', 1700000000))
        ->toBe('$1$b757dc915707ae854838ef57143ada584dcd447c');
});

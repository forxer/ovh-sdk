<?php

declare(strict_types=1);

use OvhSdk\Exceptions\AuthenticationException;
use OvhSdk\Exceptions\BadRequestException;
use OvhSdk\Exceptions\ConflictException;
use OvhSdk\Exceptions\ForbiddenException;
use OvhSdk\Exceptions\NotFoundException;
use OvhSdk\Exceptions\OvhException;
use OvhSdk\Exceptions\ServerException;
use OvhSdk\Exceptions\TooManyRequestsException;
use Saloon\Http\Faking\MockResponse;

it('maps OVH errors to typed exceptions', function (int $status, string $exception) {
    $ovh = ovh([MockResponse::make(['class' => 'Client::Any', 'message' => 'Boom'], $status)]);

    expect(fn () => $ovh->get('/me'))->toThrow($exception, 'Boom');
})->with([
    [400, BadRequestException::class],
    [401, AuthenticationException::class],
    [403, ForbiddenException::class],
    [404, NotFoundException::class],
    [409, ConflictException::class],
    [429, TooManyRequestsException::class],
    [500, ServerException::class],
    [503, ServerException::class],
    [418, OvhException::class],
]);

it('exposes the OVH error class, status, query id and response', function () {
    $ovh = ovh([MockResponse::make(
        ['class' => 'Client::Forbidden', 'message' => 'User not granted for this request'],
        403,
        ['X-Ovh-QueryId' => 'EU.ext-1.abc'],
    )]);

    expect(fn () => $ovh->get('/me'))->toThrow(function (ForbiddenException $e) {
        expect($e->errorClass)->toBe('Client::Forbidden')
            ->and($e->getMessage())->toBe('User not granted for this request')
            ->and($e->getStatus())->toBe(403)
            ->and($e->queryId)->toBe('EU.ext-1.abc')
            ->and($e->getResponse()->status())->toBe(403);
    });
});

it('survives a non JSON error body', function () {
    $ovh = ovh([MockResponse::make('<html>Bad gateway</html>', 502)]);

    expect(fn () => $ovh->get('/me'))->toThrow(ServerException::class, 'HTTP 502');
});

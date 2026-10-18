<?php

declare(strict_types=1);

use OvhSdk\Auth\ApplicationKeys;
use OvhSdk\Auth\ServiceAccount;
use OvhSdk\Data\Auth\AccessRule;
use OvhSdk\Enums\RecordType;
use OvhSdk\Exceptions\CouldNotDecodeResponse;
use OvhSdk\Exceptions\CouldNotReadResponse;
use OvhSdk\Requests\Auth\GetOAuth2Token;
use OvhSdk\Requests\Auth\GetServerTime;
use OvhSdk\Requests\Auth\RequestCredential;
use OvhSdk\Requests\Dns\CreateRecord;
use OvhSdk\Requests\Dns\ExportZone;
use OvhSdk\Requests\Dns\GetRecord;
use OvhSdk\Requests\Dns\GetRedirection;
use OvhSdk\Requests\Dns\GetZone;
use OvhSdk\Requests\Dns\GetZoneStatus;
use OvhSdk\Requests\Dns\GetZoneTask;
use OvhSdk\Requests\Dns\ListRecords;
use OvhSdk\Requests\Dns\ListRedirections;
use OvhSdk\Requests\Dns\ListZoneHistory;
use OvhSdk\Requests\Dns\ListZones;
use OvhSdk\Requests\Dns\ListZoneTasks;
use OvhSdk\Requests\Domain\GetDomain;
use OvhSdk\Requests\Domain\GetDomainServiceInfos;
use OvhSdk\Requests\Domain\ListDomains;
use OvhSdk\Requests\Generic\GenericRequest;
use Saloon\Http\Faking\MockResponse;

// Every SDK call reading a JSON body, answered with the given successful response.
dataset('json reads', [
    'domain list' => [fn (MockResponse $response) => ovh([ListDomains::class => $response])->domain()->ids()],
    'domain' => [fn (MockResponse $response) => ovh([GetDomain::class => $response])->domain()->get('example.com')],
    'domain service information' => [fn (MockResponse $response) => ovh([GetDomainServiceInfos::class => $response])->domain()->serviceInfos('example.com')],
    'zone list' => [fn (MockResponse $response) => ovh([ListZones::class => $response])->domain()->zones()->ids()],
    'zone' => [fn (MockResponse $response) => ovh([GetZone::class => $response])->domain()->zone('example.com')->get()],
    'zone status' => [fn (MockResponse $response) => ovh([GetZoneStatus::class => $response])->domain()->zone('example.com')->status()],
    'zone export' => [fn (MockResponse $response) => ovh([ExportZone::class => $response])->domain()->zone('example.com')->export()],
    'zone history' => [fn (MockResponse $response) => ovh([ListZoneHistory::class => $response])->domain()->zone('example.com')->history()],
    'record list' => [fn (MockResponse $response) => ovh([ListRecords::class => $response])->domain()->zone('example.com')->records()->ids()],
    'record' => [fn (MockResponse $response) => ovh([GetRecord::class => $response])->domain()->zone('example.com')->records()->get(42)],
    'created record' => [fn (MockResponse $response) => ovh([CreateRecord::class => $response])->domain()->zone('example.com')->records()->create(RecordType::A, '192.0.2.1')],
    'redirection list' => [fn (MockResponse $response) => ovh([ListRedirections::class => $response])->domain()->zone('example.com')->redirections()->ids()],
    'redirection' => [fn (MockResponse $response) => ovh([GetRedirection::class => $response])->domain()->zone('example.com')->redirections()->get(42)],
    'zone task list' => [fn (MockResponse $response) => ovh([ListZoneTasks::class => $response])->domain()->zone('example.com')->tasks()->ids()],
    'zone task' => [fn (MockResponse $response) => ovh([GetZoneTask::class => $response])->domain()->zone('example.com')->tasks()->get(42)],
    'credential' => [fn (MockResponse $response) => ovh([RequestCredential::class => $response], new ApplicationKeys('app-key', 'app-secret'))
        ->requestConsumerKey([AccessRule::fromString('GET:/me')])],
    'server time' => [fn (MockResponse $response) => ovh([GetServerTime::class => $response, GenericRequest::class => MockResponse::make([])], new ApplicationKeys('app-key', 'app-secret', 'consumer-key'))
        ->get('/me')],
    'OAuth2 token' => [fn (MockResponse $response) => ovh([GetOAuth2Token::class => $response, GenericRequest::class => MockResponse::make([])], new ServiceAccount('client-id', 'client-secret'))
        ->get('/me')],
]);

it('throws a CouldNotDecodeResponse when a successful body is not JSON', function (Closure $read, string $body) {
    expect(fn () => $read(MockResponse::make($body)))->toThrow(CouldNotDecodeResponse::class);
})->with('json reads')->with([
    'an HTML page' => '<html>Maintenance</html>',
    'an empty body' => '',
]);

it('throws a CouldNotReadResponse, not a CouldNotDecodeResponse, when a successful body is JSON of the wrong shape', function (Closure $read) {
    expect(fn () => $read(MockResponse::make('true')))->toThrow(function (CouldNotReadResponse $exception) {
        expect($exception)->not->toBeInstanceOf(CouldNotDecodeResponse::class);
    });
})->with('json reads');

it('names the call and keeps the JSON error when a body is not JSON', function () {
    $ovh = ovh([ListDomains::class => MockResponse::make('<html>Maintenance</html>')]);

    expect(fn () => $ovh->domain()->ids())->toThrow(function (CouldNotDecodeResponse $exception) {
        expect($exception->getMessage())->toBe('OVH response to GET https://api.eu.ovhcloud.com/v1/domain is not valid JSON: Syntax error.')
            ->and($exception->getPrevious())->toBeInstanceOf(JsonException::class);
    });
});

it('names the response that is JSON of the wrong shape', function (Closure $read, string $body, string $message) {
    expect(fn () => $read(MockResponse::make($body)))->toThrow(CouldNotReadResponse::class, $message);
})->with([
    'a string for a list' => [fn (MockResponse $response) => ovh([ListDomains::class => $response])->domain()->ids(), '"chaine"', 'OVH domain list response is not a list.'],
    'a string for an object' => [fn (MockResponse $response) => ovh([GetZone::class => $response])->domain()->zone('example.com')->get(), '"chaine"', 'OVH zone response is not an object.'],
    'a list for an object' => [fn (MockResponse $response) => ovh([GetZone::class => $response])->domain()->zone('example.com')->get(), '[1, 2]', 'OVH zone response is not an object.'],
    'a string for the server time' => [fn (MockResponse $response) => ovh([GetServerTime::class => $response], new ApplicationKeys('app-key', 'app-secret', 'consumer-key'))->get('/me'), '"chaine"', 'OVH server time response is not an integer.'],
    'a string for the OAuth2 token' => [fn (MockResponse $response) => ovh([GetOAuth2Token::class => $response], new ServiceAccount('client-id', 'client-secret'))->get('/me'), '"chaine"', 'OVH OAuth2 token response is not an object.'],
]);

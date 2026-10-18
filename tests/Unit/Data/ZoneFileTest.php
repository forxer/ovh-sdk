<?php

declare(strict_types=1);

use OvhSdk\Data\Dns\ZoneFile;
use OvhSdk\Data\Dns\ZoneFileRecord;
use OvhSdk\Enums\RecordType;
use OvhSdk\Exceptions\CouldNotParseZoneFile;
use OvhSdk\Exceptions\InvalidZoneName;

/**
 * @return list<array{string, ?int, string, string}>
 */
function zoneFileRows(ZoneFile $zoneFile): array
{
    return array_map(
        fn (ZoneFileRecord $record) => [$record->subDomain, $record->ttl, $record->type->name, $record->target],
        $zoneFile->records,
    );
}

it('splits a zone file exported by OVHcloud, without its SOA record', function () {
    $zoneFile = ZoneFile::parse((string) file_get_contents(fixture('zone-export.txt')), 'example.com');

    expect($zoneFile->defaultTtl)->toBe(3600)
        ->and(zoneFileRows($zoneFile))->toBe([
            ['', null, 'NS', 'dns200.anycast.me.'],
            ['', null, 'NS', 'ns200.anycast.me.'],
            ['', null, 'MX', '1 mx1.mail.ovh.net.'],
            ['', null, 'A', '203.0.113.10'],
            ['', null, 'TXT', '"v=spf1 include:mx.ovh.com -all"'],
            ['www', null, 'A', '203.0.113.10'],
            ['app', 3600, 'CNAME', 'www.example.com.'],
        ]);
});

it('splits every syntax of a zone file', function () {
    $zoneFile = ZoneFile::parse((string) file_get_contents(fixture('zone-file.txt')), 'example.com');

    expect($zoneFile->defaultTtl)->toBe(3600)
        ->and(zoneFileRows($zoneFile))->toBe([
            ['', null, 'NS', 'dns200.anycast.me.'],
            ['', null, 'NS', 'ns200.anycast.me.'],
            ['', null, 'MX', '1 mx1.mail.ovh.net.'],
            ['', null, 'MX', '5 mx2.mail.ovh.net.'],
            ['www', 300, 'A', '203.0.113.10'],
            ['www', 300, 'AAAA', '2001:db8::10'],
            ['Blog', null, 'CNAME', 'www'],
            ['_dmarc', null, 'TXT', '"v=DMARC1; p=none; rua=mailto:dmarc@example.com"'],
            ['selector._domainkey', null, 'TXT', '"v=DKIM1; k=rsa; " "p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA"'],
            ['quote', null, 'TXT', '"say \"hello\"; then leave"'],
            ['_sip._tcp', null, 'SRV', '10 60 5060 sip.example.com.'],
            ['mail', null, 'A', '203.0.113.20'],
            ['smtp.mail', null, 'A', '203.0.113.21'],
            ['info.mail', null, 'Unknown', '"PC" "Linux"'],
        ]);
});

it('accepts the zone name with its final dot and in any case', function () {
    $zoneFile = ZoneFile::parse("www.example.com. IN A 203.0.113.10\n", 'Example.COM.');

    expect(zoneFileRows($zoneFile))->toBe([['www', null, 'A', '203.0.113.10']]);
});

it('reads the type and class in any case, and windows line breaks', function () {
    $zoneFile = ZoneFile::parse("www in a 203.0.113.10\r\n\tin cname www.example.net.\r\n", 'example.com');

    expect(zoneFileRows($zoneFile))->toBe([
        ['www', null, 'A', '203.0.113.10'],
        ['www', null, 'CNAME', 'www.example.net.'],
    ]);
});

it('reads a ttl written with units', function (string $ttl, int $seconds) {
    expect(ZoneFile::parse("www {$ttl} IN A 203.0.113.10", 'example.com')->records[0]->ttl)->toBe($seconds);
})->with([
    'seconds' => ['0', 0],
    'one unit' => ['2d', 172800],
    'several units, any case' => ['1W1h30M', 610200],
]);

it('accepts a repeated $TTL directive of the same value', function () {
    expect(ZoneFile::parse("\$TTL 3600\n\$TTL 1h\n", 'example.com')->defaultTtl)->toBe(3600);
});

it('reads an empty zone file', function () {
    $zoneFile = ZoneFile::parse("; nothing but a comment\n\n", 'example.com');

    expect($zoneFile->defaultTtl)->toBeNull()
        ->and($zoneFile->records)->toBe([]);
});

it('refuses a zone file it cannot understand', function (string $content, string $message) {
    expect(fn () => ZoneFile::parse($content, 'example.com'))->toThrow(CouldNotParseZoneFile::class, $message);
})->with([
    'a name outside the zone' => ["www.example.org. IN A 203.0.113.10\n", 'Zone file, line 1: "www.example.org." is outside the zone "example.com.".'],
    'a first record without owner' => ["\$TTL 3600\n  IN A 203.0.113.10\n", 'Zone file, line 2: a record without owner must follow another record.'],
    'an unterminated quoted string' => ["www IN TXT \"v=spf1 -all\n", 'Zone file, line 1: unterminated quoted string or escape.'],
    'a parenthesis left open' => ["@ IN SOA dns200.anycast.me. tech.ovh.net. (\n 2026101801 86400\n", 'Zone file, line 1: parenthesis left open.'],
    'a closing parenthesis alone' => ["www IN A 203.0.113.10 )\n", 'Zone file, line 1: closing parenthesis without an opening one.'],
    'an unsupported directive' => ["\$INCLUDE other.zone\n", 'Zone file, line 1: the $INCLUDE directive is not supported.'],
    'a directive without value' => ["\$ORIGIN\n", 'Zone file, line 1: the $ORIGIN directive has no value.'],
    'a $TTL that is not a ttl' => ["\$TTL soon\n", 'Zone file, line 1: "soon" is not a TTL.'],
    'a second $TTL of another value' => ["\$TTL 3600\nwww IN A 203.0.113.10\n\$TTL 60\n", 'Zone file, line 3: a second $TTL directive changes the default TTL.'],
    'a record without type' => ["www 300 IN\n", 'Zone file, line 1: record without type.'],
    'a record without data' => ["www IN A\n", 'Zone file, line 1: the A record has no data.'],
]);

it('gives the line it cannot read', function () {
    expect(fn () => ZoneFile::parse("\$TTL 3600\nwww IN A 203.0.113.10\n\$TTL 60\n", 'example.com'))
        ->toThrow(fn (CouldNotParseZoneFile $exception) => expect($exception->zoneFileLine)->toBe(3));
});

it('refuses an empty zone name', function () {
    expect(fn () => ZoneFile::parse('', '.'))->toThrow(InvalidZoneName::class, 'A zone file needs the name of its zone, such as "example.com", "." given.');
});

it('keeps an unknown type out of the known types', function () {
    expect(ZoneFile::parse('www IN TYPE65534 \# 0', 'example.com')->records[0]->type)->toBe(RecordType::Unknown);
});

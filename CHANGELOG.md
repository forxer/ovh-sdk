CHANGELOG
=========

Unreleased
----------

- Saloon connector `OvhSdk\Ovh` with the EU, CA and US regions, and generic `get()`, `post()`, `put()` and `delete()` calls on any endpoint
- Typed exceptions for OVHcloud errors, exposing the OVH error class and the query id
- Application keys authentication, with request signature and server clock synchronisation
- OAuth2 service account authentication, with a cached token
- Consumer key request with `Ovh::requestConsumerKey()`
- Domains: names, details and service information
- DNS zones: names, details and refresh
- DNS records: list, read, create, update and delete
- Laravel 13 integration: configuration, facade, injection, cache, `Ovh::fake()` and `ovh:consumer-key` command
- Documentation

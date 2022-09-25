# CockroachDB Driver for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ylsideas/cockroachdb-laravel.svg?style=flat-square)](https://packagist.org/packages/ylsideas/cockroachdb-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/ylsideas/cockroachdb-laravel/run-tests?label=tests)](https://github.com/ylsideas/cockroachdb-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/ylsideas/cockroachdb-laravel/Check%20&%20fix%20styling?label=code%20style)](https://github.com/ylsideas/cockroachdb-laravel/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ylsideas/cockroachdb-laravel.svg?style=flat-square)](https://packagist.org/packages/ylsideas/cockroachdb-laravel)

A driver/grammar for Laravel that works with CockroachDB. While CockroachDB is compatible with Postgresql, this support
is not 1 to 1 meaning you may run into issues, this driver hopes to resolve those problems as much as possible.

Laravel 8 and 9 are both supported and tested against CockroachDB 2.5.

### Supporting Open Source

[Peter Fox](https://www.peterfox.me) here, I just want to say this project has been my hardest yet. It's been a real labour of love to make and takes
up a lot of time trying to organise the test suite so that compatibility is maintained between Eloquent and CockroachDB.

I see a lot of promise in using CockroachDB's serverless offering which is what compelled me to go down this route originally.
You can read [an article](https://medium.com/@SlyFireFox/laravel-tip-cockroachdbs-serverless-database-322aa7f5f7ef) 
I made about using their service.

If you're using this project at all then do please consider [sponsoring me](https://github.com/sponsors/peterfox) 
as a way of encouraging more development.

## Installation

You can install the package via composer:

```bash
composer require ylsideas/cockroachdb-laravel
```

You need to add the connection type to the database config:
```php
'crdb' => [
    'driver' => 'crdb',
    'url' => env('DATABASE_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '26257'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8',
    'prefix' => '',
    'prefix_indexes' => true,
    'schema' => 'public',
    'sslmode' => 'prefer',
]
```

## Usage

To enable set `DB_CONNECTION=crdb` in your .env.

## Notes

CockroachDB should work inline with the feature set of Postgresql, with some exceptions. You can look at the
features of each CockroachDB server in the CockroachDB [Docs](https://www.cockroachlabs.com/docs/stable/sql-feature-support.html).

### Deletes with Joins
CockroachDB does not support performing deletes using joins. If you wish to
do something like this you will need to use a sub-query instead.

At current if you try to call the `delete` method of the Query builder together with a `join` then
a `YlsIdeas\CockroachDb\Exceptions\FeatureNotSupportedException` exception will be thrown.

### Fulltext Search
Eloquent and Postgresql support Fulltext search. CockroachDB does not support any full text
search meaning the feature cannot be used when using this driver.

At current if you try to create a Fulltext index using the Schema builder or try to use the `whereFulltext`
method of the Query builder a `YlsIdeas\CockroachDb\Exceptions\FeatureNotSupportedException` exception will be thrown.

### Serverless Support
Cockroach Serverless requires you to add an `options` parameter to the connection string.
Laravel doesn't provide this out of the box, so, it's being implemented as an extra `cluster` parameter in the database config. Just pass the cluster identification from CockroachDB Serverless.

Sample config snippet:

```php
'crdb' => [
    'driver' => 'crdb',
    'url' => env('DATABASE_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '26257'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8',
    'prefix' => '',
    'prefix_indexes' => true,
    'schema' => 'public',
    'sslmode' => 'prefer',
    'cluster' => env('COCKROACHDB_CLUSTER', ''),
]
```

## Testing

The tests try to closely follow the same functionality of the grammar provided by Laravel
by lifting the tests straight from laravel/framework. This does provide some complications.
Namely, cockroachdb is designed to be distributed so primary keys do not occur in sequence.

Tests should also try to be compatible with not just the latest version of Laravel but across
Laravel 8 and 9, this requires some tests to be skipped.

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Peter Fox](https://github.com/peterfox)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

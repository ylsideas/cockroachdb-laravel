# CockroachDB Driver for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ylsideas/cockroachdb-laravel.svg?style=flat-square)](https://packagist.org/packages/ylsideas/cockroachdb-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/ylsideas/cockroachdb-laravel/run-tests?label=tests)](https://github.com/ylsideas/cockroachdb-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/ylsideas/cockroachdb-laravel/Check%20&%20fix%20styling?label=code%20style)](https://github.com/ylsideas/cockroachdb-laravel/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ylsideas/cockroachdb-laravel.svg?style=flat-square)](https://packagist.org/packages/ylsideas/cockroachdb-laravel)

A driver/grammar for Laravel that works with CockroachDB. This is currently an alpha.
All tests pass, but you may run into bugs potentially going forward.

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

CockroachDB does not support performing deletes using joins. If you wish to
do something like this you will need to use a sub-query instead.

## Testing

The tests try to closely follow the same functionality of the grammar provided by Laravel
by lifting the tests straight from laravel/framework. This does provide some complications.
Namely, cockroachdb is designed to be distributed so primary keys do not occur in sequence.
Also deletes with joins do not work compared to other Laravel supported databases so the 
delete with limit test uses a sub query instead.

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

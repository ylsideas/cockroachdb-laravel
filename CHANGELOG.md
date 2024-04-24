# Changelog

All notable changes to `cockroachdb-laravel` will be documented in this file.

## v1.4.0 - 2024-03-24

### What's Changed

* Upgrade to Laravel 11 by @peterfox in https://github.com/ylsideas/cockroachdb-laravel/pull/37

**Full Changelog**: https://github.com/ylsideas/cockroachdb-laravel/compare/v1.3.0...v1.4.0

## v1.3.0 - 2024-01-14

### What's Changed

* Added Schema Dump functionality by @peterfox in https://github.com/ylsideas/cockroachdb-laravel/pull/30
* Drops support for PHP 8.0 and Laravel 8 by @peterfox in https://github.com/ylsideas/cockroachdb-laravel/pull/30

**Full Changelog**: https://github.com/ylsideas/cockroachdb-laravel/compare/v1.2.1...v1.3.0

## v1.2.1 - 2023-12-02

### What's Changed

* fix: Any artisan command that drops tables erroring with unknown function: pg_total_relation_size() by @wsamoht in https://github.com/ylsideas/cockroachdb-laravel/pull/26

### New Contributors

* @wsamoht made their first contribution in https://github.com/ylsideas/cockroachdb-laravel/pull/26

**Full Changelog**: https://github.com/ylsideas/cockroachdb-laravel/compare/v1.2.0...v1.2.1

## v1.2.0 - Laravel 10 Support - 2023-03-11

### What's Changed

- Adds Laravel 10 support by @peterfox in https://github.com/ylsideas/cockroachdb-laravel/pull/17

**Full Changelog**: https://github.com/ylsideas/cockroachdb-laravel/compare/v1.1.1...v1.2.0

## v1.1.1 - 2022-10-05

### What's Changed

- CockroachDB Database URLs including serverless usage are now fully supported.

**Full Changelog**: https://github.com/ylsideas/cockroachdb-laravel/compare/v1.1.0...v1.1.1

## v1.1.0 - 2022-09-26

### What's Changed

- Add support for Cockroach Serverless Cluster identifier by @tryvin in https://github.com/ylsideas/cockroachdb-laravel/pull/11
- Drop unique indexes fixes by @tryvin in https://github.com/ylsideas/cockroachdb-laravel/pull/10

**Full Changelog**: https://github.com/ylsideas/cockroachdb-laravel/compare/v1.0.1...v1.1.0

## Fixed Truncating tables - 2022-09-22

### Changes

- Fixes truncating tables, thanks to @tryvin for pointing this out and providing the code to fix this.

## Stable Release with Laravel 9 and 8 support - 2022-02-12

# Changes

Support for Laravel 9 and 8 locked in. See [notes](./README.md#notes) for exceptions to the compatibility.

## 0.1.0 - 2021-12-14

- initial alpha release

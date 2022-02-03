<?php

use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase;
use YlsIdeas\CockroachDb\CockroachDbServiceProvider;

uses(TestCase::class);

test('refresh without realpath', function () {
    app()->setBasePath(__DIR__);

    $options = [
        '--path' => 'stubs/',
    ];

    migrateRefreshWith($options);
});

test('refresh with realpath', function () {
    $options = [
        '--path' => realpath(__DIR__.'/stubs/'),
        '--realpath' => true,
    ];

    migrateRefreshWith($options);
});

// Helpers
/**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
function getPackageProviders($app)
{
    return [
        CockroachDbServiceProvider::class,
    ];
}

function getEnvironmentSetUp($app)
{
    config()->set('database.connections.crdb', [
        'driver' => 'crdb',
        'url' => env('DATABASE_URL'),
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '26257'),
        'database' => env('DB_DATABASE', 'forge'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'schema' => 'public',
        'sslmode' => 'prefer',
    ]);
}

function migrateRefreshWith(array $options)
{
    if (test()->app['config']->get('database.default') !== 'testing') {
        test()->artisan('db:wipe', ['--drop-views' => true]);
    }

    test()->beforeApplicationDestroyed(function () use ($options) {
        test()->artisan('migrate:rollback', $options);
    });

    test()->artisan('migrate:refresh', $options);
    DB::table('members')->insert(['name' => 'foo', 'email' => 'foo@bar', 'password' => 'secret']);
    expect(DB::table('members')->count())->toEqual(1);

    test()->artisan('migrate:refresh', $options);
    expect(DB::table('members')->count())->toEqual(0);
}

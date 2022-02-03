<?php

use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;
use YlsIdeas\CockroachDb\CockroachDbServiceProvider;

uses(TestCase::class);

beforeEach(function () {
    if (app()['config']->get('database.default') !== 'testing') {
        $this->artisan('db:wipe', ['--drop-views' => true]);
    }

    $options = [
        '--path' => realpath(__DIR__.'/stubs/'),
        '--realpath' => true,
    ];

    $this->artisan('migrate', $options);

    $this->beforeApplicationDestroyed(function () use ($options) {
        $this->artisan('migrate:rollback', $options);
    });
});

test('realpath migration has properly executed', function () {
    $this->assertTrue(Schema::hasTable('members'));
});

test('migrations has the migrated table', function () {
    $this->assertDatabaseHas('migrations', [
        'migration' => '2014_10_12_000000_create_members_table',
        'batch' => 1,
    ]);
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

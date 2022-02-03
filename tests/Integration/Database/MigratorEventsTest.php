<?php

use Illuminate\Database\Events\MigrationEnded;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Database\Events\MigrationStarted;
use Illuminate\Database\Events\NoPendingMigrations;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;
use YlsIdeas\CockroachDb\CockroachDbServiceProvider;

uses(TestCase::class);

test('migration events are fired', function () {
    Event::fake();

    $this->artisan('migrate', migrateOptions());
    $this->artisan('migrate:rollback', migrateOptions());

    Event::assertDispatched(MigrationsStarted::class, 2);
    Event::assertDispatched(MigrationsEnded::class, 2);
    Event::assertDispatched(MigrationStarted::class, 2);
    Event::assertDispatched(MigrationEnded::class, 2);
});

test('migration events contain the migration and method', function () {
    Event::fake();

    $this->artisan('migrate', migrateOptions());
    $this->artisan('migrate:rollback', migrateOptions());

    Event::assertDispatched(MigrationStarted::class, function ($event) {
        return $event->method === 'up' && $event->migration instanceof Migration;
    });
    Event::assertDispatched(MigrationStarted::class, function ($event) {
        return $event->method === 'down' && $event->migration instanceof Migration;
    });
    Event::assertDispatched(MigrationEnded::class, function ($event) {
        return $event->method === 'up' && $event->migration instanceof Migration;
    });
    Event::assertDispatched(MigrationEnded::class, function ($event) {
        return $event->method === 'down' && $event->migration instanceof Migration;
    });
});

test('the no migration event is fired when nothing to migrate', function () {
    Event::fake();

    $this->artisan('migrate');
    $this->artisan('migrate:rollback');

    Event::assertDispatched(NoPendingMigrations::class, function ($event) {
        return $event->method === 'up';
    });
    Event::assertDispatched(NoPendingMigrations::class, function ($event) {
        return $event->method === 'down';
    });
});

// Helpers
function migrateOptions()
{
    return [
        '--path' => realpath(__DIR__.'/stubs/'),
        '--realpath' => true,
    ];
}

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

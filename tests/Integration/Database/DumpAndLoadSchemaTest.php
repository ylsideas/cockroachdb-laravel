<?php

namespace YlsIdeas\CockroachDb\Tests\Integration\Database;

use Illuminate\Support\Facades\File;

class DumpAndLoadSchemaTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_exporting_an_sql_dump()
    {
        File::ensureDirectoryExists(database_path('schema-test'));

        if ($this->app['config']->get('database.default') !== 'testing') {
            $this->artisan('db:wipe', ['--drop-views' => true]);
        }

        $options = [
            '--path' => realpath(__DIR__.'/stubs/schema-dump-migrations'),
            '--realpath' => true,
        ];

        $this->artisan('migrate', $options);

        $this->beforeApplicationDestroyed(function () use ($options) {
            File::delete(database_path('schema-test/crdb-schema.sql'));

            $this->artisan('migrate:rollback', $options);
        });

        $this->artisan('schema:dump', [
            '--database' => 'crdb',
            '--path' => database_path('schema-test/crdb-schema.sql'),
        ])
            ->assertSuccessful();

        $this->assertFileExists(database_path('schema-test/crdb-schema.sql'));
    }

    public function test_importing_an_sql_dump()
    {
        // Make sure the schema direct exists first
        File::ensureDirectoryExists(database_path('schema-test'));
        File::copy(__DIR__ . '/stubs/schema-dump.sql', database_path('schema-test/crdb-schema.sql'));

        if ($this->app['config']->get('database.default') !== 'testing') {
            $this->artisan('db:wipe', ['--drop-views' => true]);
        }

        $options = [
            '--path' => realpath(__DIR__.'/stubs/schema-dump-migrations'),
            '--realpath' => true,
        ];

        $this->beforeApplicationDestroyed(function () use ($options) {
            File::delete(database_path('schema/crdb-schema.sql'));

            $this->artisan('migrate:rollback', $options);
        });

        $this->artisan('migrate', [
            ...$options,
            ...['--schema-path' => database_path('schema-test/crdb-schema.sql')]
        ]);

        $this->assertDatabaseCount('migrations', 2);

        $this->assertDatabaseHas('migrations', [
            'migration' => '2014_10_12_000000_create_members_table',
        ]);

        $this->assertDatabaseHas('migrations', [
            'migration' => '2014_10_12_000000_create_users_table',
        ]);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('members', 0);
    }
}

<?php

namespace YlsIdeas\CockroachDb\Tests\Integration\Database;

use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase;
use YlsIdeas\CockroachDb\CockroachDbServiceProvider;

class RefreshCommandTest extends TestCase
{
    /**
     * @doesNotPerformAssertions
     */
    public function test_refresh_without_realpath()
    {
        $this->app->setBasePath(__DIR__);

        $options = [
            '--path' => 'stubs/',
        ];

        $this->migrateRefreshWith($options);
    }

    public function test_refresh_with_realpath()
    {
        $options = [
            '--path' => realpath(__DIR__.'/stubs/'),
            '--realpath' => true,
        ];

        $this->migrateRefreshWith($options);
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            CockroachDbServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        config()->set('database.connections.crdb', [
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
        ]);
    }

    private function migrateRefreshWith(array $options)
    {
        if ($this->app['config']->get('database.default') !== 'testing') {
            $this->artisan('db:wipe', ['--drop-views' => true]);
        }

        $this->beforeApplicationDestroyed(function () use ($options) {
            $this->artisan('migrate:rollback', $options);
        });

        $this->artisan('migrate:refresh', $options);
        DB::table('members')->insert(['name' => 'foo', 'email' => 'foo@bar', 'password' => 'secret']);
        $this->assertEquals(1, DB::table('members')->count());

        $this->artisan('migrate:refresh', $options);
        $this->assertEquals(0, DB::table('members')->count());
    }
}

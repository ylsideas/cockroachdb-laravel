<?php

namespace YlsIdeas\CockroachDb\Tests\Integration\Database;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Orchestra\Testbench\TestCase;
use YlsIdeas\CockroachDb\CockroachDbServiceProvider;

abstract class DatabaseTestCase extends TestCase
{
    use DatabaseMigrations;

    /**
     * The current database driver.
     *
     * @return string
     */
    protected $driver;

    protected function setUp(): void
    {
        $this->beforeApplicationDestroyed(function () {
            foreach (array_keys($this->app['db']->getConnections()) as $name) {
                $this->app['db']->purge($name);
            }
        });

        parent::setUp();
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
//            \Spatie\LaravelRay\RayServiceProvider::class,
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

        $connection = $app['config']->get('database.default');

        $this->driver = $app['config']->get("database.connections.$connection.driver");
    }
}

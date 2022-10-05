<?php

namespace YlsIdeas\CockroachDb\Tests\Database;

use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;
use YlsIdeas\CockroachDb\CockroachDbConnector;

class DatabaseCockroachDbConnectorTest extends TestCase
{
    public function test_dsn_params_with_cluster()
    {
        $connector = $this->getConnector();

        $dsnConfig = $connector->exposeGetDsnMethod(
            [
                'host' => 'localhost',
                'database' => 'defaultdb',
                'port' => '23456',
                'cluster' => 'cluster-1234',
            ],
        );

        if (version_compare(Application::VERSION, '8.81.0', '>=')) {
            $this->assertStringContainsString('dbname=\'cluster-1234.defaultdb\'', $dsnConfig);
        } else {
            $this->assertStringContainsString('dbname=cluster-1234.defaultdb', $dsnConfig);
        }
    }

    public function test_dsn_params_without_cluster()
    {
        $connector = $this->getConnector();

        $dsnConfig = $connector->exposeGetDsnMethod(
            [
                'host' => 'localhost',
                'database' => 'defaultdb',
                'port' => '23456',
                'cluster' => '',
            ],
        );

        if (version_compare(Application::VERSION, '8.81.0', '>=')) {
            $this->assertStringContainsString('dbname=\'defaultdb\'', $dsnConfig);
        } else {
            $this->assertStringContainsString('dbname=defaultdb', $dsnConfig);
        }
    }

    protected function getConnector()
    {
        return new class () extends CockroachDbConnector {
            public function exposeGetDsnMethod(array $config): string
            {
                return $this->getDsn($config);
            }
        };
    }
}

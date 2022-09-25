<?php

namespace YlsIdeas\CockroachDb\Tests\Database;

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

        $this->assertStringContainsString("options='--cluster=cluster-1234'", $dsnConfig);
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

        $this->assertStringNotContainsString("options=", $dsnConfig);
    }

    protected function getConnector()
    {
        return new class extends CockroachDbConnector {
            public function exposeGetDsnMethod(array $config) {
                return $this->getDsn($config);
            }
        };
    }
}

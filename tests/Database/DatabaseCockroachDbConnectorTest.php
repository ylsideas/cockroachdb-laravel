<?php

namespace YlsIdeas\CockroachDb\Tests\Database;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use YlsIdeas\CockroachDb\CockroachDbConnector;

class DatabaseCockroachDbConnectorTest extends TestCase
{
    public function test_dsn_params_with_cluster()
    {
        $connector = $this->getConnector();

        $dsnConfig = $this->callProtectedOrPrivateMethod($connector, 'getDsn', [
            [
                'host' => 'localhost',
                'database' => 'defaultdb',
                'port' => '23456',
                'cluster' => 'cluster-1234',
            ],
        ]);

        $this->assertStringContainsString("options='--cluster=cluster-1234'", $dsnConfig);
    }

    public function test_dsn_params_without_cluster()
    {
        $connector = $this->getConnector();

        $dsnConfig = $this->callProtectedOrPrivateMethod($connector, 'getDsn', [
            [
                'host' => 'localhost',
                'database' => 'defaultdb',
                'port' => '23456',
                'cluster' => '',
            ],
        ]);

        $this->assertStringNotContainsString("options=", $dsnConfig);
    }

    /**
     * Some methods might be protected and need to be tested. So this just exposes them using reflection.
     */
    protected function callProtectedOrPrivateMethod($object, string $methodName, array $arguments = [])
    {
        $reflectionClass = new ReflectionClass($object);
        $reflectionMethod = $reflectionClass->getMethod($methodName);
        $reflectionMethod->setAccessible(true);

        return empty($arguments) ?
            $reflectionMethod->invoke($object) :
            $reflectionMethod->invokeArgs($object, $arguments);
    }

    protected function getConnector()
    {
        return new CockroachDbConnector();
    }
}

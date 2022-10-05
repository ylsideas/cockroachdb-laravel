<?php

namespace YlsIdeas\CockroachDb\Tests\Database;

use Illuminate\Support\ConfigurationUrlParser;
use PHPUnit\Framework\TestCase;

class ConfigurationUrlParserTest extends TestCase
{
    public function test_configuration_url_parser_can_parse_cockroach_db_urls()
    {
        ConfigurationUrlParser::addDriverAlias('cockroachdb', 'crdb');

        $parser = new ConfigurationUrlParser();

        $config = $parser->parseConfiguration([
            'url' => 'cockroachdb://username:password@hostname.example.com:26257/defaultdb?sslmode=verify-full&cluster=my-cluster-123',
        ]);

        $this->assertSame([
            'driver' => 'crdb',
            'database' => 'defaultdb',
            'host' => 'hostname.example.com',
            'port' => 26257,
            'username' => 'username',
            'password' => 'password',
            'sslmode' => 'verify-full',
            'cluster' => 'my-cluster-123',
        ], $config);
    }
}

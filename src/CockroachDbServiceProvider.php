<?php

namespace YlsIdeas\CockroachDb;

use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;

class CockroachDbServiceProvider extends ServiceProvider
{
    public function register()
    {
        Connection::resolverFor('crdb', function ($connection, $database, $prefix, $config) {
            $connector = new CockroachDbConnector();
            $connection = $connector->connect($config);

            return new CockroachDbConnection($connection, $database, $prefix, $config);
        });
    }
}

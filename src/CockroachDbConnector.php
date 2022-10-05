<?php

namespace YlsIdeas\CockroachDb;

use Illuminate\Database\Connectors\ConnectorInterface;
use Illuminate\Database\Connectors\PostgresConnector;

class CockroachDbConnector extends PostgresConnector implements ConnectorInterface
{
    /**
     * When using CockroachDB serverless it's possible to apply a namespace to the name of the database
     * which then allows for the service to recognise which cluster is being used.
     */
    protected function getDsn(array $config): string
    {
        if (($config['cluster'] ?? false) && $config['cluster'] != '') {
            $config['database'] = implode('.', [$config['cluster'], $config['database']]);
        }

        return parent::getDsn($config);
    }
}

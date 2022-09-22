<?php

namespace YlsIdeas\CockroachDb;

use Illuminate\Database\Connectors\ConnectorInterface;
use Illuminate\Database\Connectors\PostgresConnector;

class CockroachDbConnector extends PostgresConnector implements ConnectorInterface
{
    /**
     * Usually the normal PostgresConnector would suffice for Cockroach,
     * but Cockroach Serverless Clusters need an extra parameter `options`.
     */
    protected function getDsn(array $config)
    {
        $dsn = parent::getDsn($config);

        if (isset($config['cluster']) && !empty($config['cluster'])) {
            $dsn .= ";options='--cluster={$config['cluster']}'";
        }

        return $dsn;
    }
}

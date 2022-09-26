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
        return $this->addClusterOptions(parent::getDsn($config), $config);
    }

    protected function addClusterOptions(string $dsn, array $config)
    {
        if (isset($config['cluster']) && ! empty($config['cluster'])) {
            $clusterNameEscaped = addslashes($config['cluster']);
            $dsn .= ";options='--cluster={$clusterNameEscaped}'";
        }

        return $dsn;
    }
}

<?php

namespace YlsIdeas\CockroachDb;

use Illuminate\Database\Connectors\ConnectorInterface;
use Illuminate\Database\Connectors\PostgresConnector;

class CockroachDbConnector extends PostgresConnector implements ConnectorInterface
{
}

<?php

namespace YlsIdeas\CockroachDb\Driver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Illuminate\Database\PDO\PostgresDriver;
use YlsIdeas\CockroachDb\Schema\CockroachSchemaManager;

class CockroachDbDriver extends PostgresDriver
{
    public function getSchemaManager(Connection $conn, AbstractPlatform $platform): AbstractSchemaManager
    {
        return new CockroachSchemaManager($conn, $platform);
    }
}

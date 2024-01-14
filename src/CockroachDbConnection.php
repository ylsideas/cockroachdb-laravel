<?php

namespace YlsIdeas\CockroachDb;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Grammar as BaseGrammar;
use Illuminate\Database\PDO\PostgresDriver;
use Illuminate\Database\PostgresConnection;
use Illuminate\Filesystem\Filesystem;
use YlsIdeas\CockroachDb\Builder\CockroachDbBuilder as DbBuilder;
use YlsIdeas\CockroachDb\Processor\CockroachDbProcessor as DbProcessor;
use YlsIdeas\CockroachDb\Query\CockroachGrammar as QueryGrammar;
use YlsIdeas\CockroachDb\Schema\CockroachGrammar as SchemaGrammar;
use YlsIdeas\CockroachDb\Schema\CockroachSchemaState as SchemaState;

class CockroachDbConnection extends PostgresConnection implements ConnectionInterface
{
    /**
     * Get the default query grammar instance.
     *
     * @return BaseGrammar
     */
    protected function getDefaultQueryGrammar(): BaseGrammar
    {
        return $this->withTablePrefix((new QueryGrammar())->setConnection($this));
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return DbBuilder
     */
    public function getSchemaBuilder(): DbBuilder
    {
        if ($this->schemaGrammar === null) {
            $this->useDefaultSchemaGrammar();
        }

        return new DbBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return BaseGrammar
     */
    protected function getDefaultSchemaGrammar(): BaseGrammar
    {
        return $this->withTablePrefix((new SchemaGrammar())->setConnection($this));
    }

    /**
     * Get the schema state for the connection.
     * @return SchemaState
     */
    public function getSchemaState(Filesystem $files = null, callable $processFactory = null)
    {
        return new SchemaState($this, $files, $processFactory);
    }

    /**
     * Get the default post processor instance.
     *
     * @return DbProcessor
     */
    protected function getDefaultPostProcessor(): DbProcessor
    {
        return new DbProcessor();
    }

    /**
     * Get the Doctrine DBAL driver.
     *
     * @return \Illuminate\Database\PDO\PostgresDriver
     */
    protected function getDoctrineDriver()
    {
        return new PostgresDriver();
    }
}

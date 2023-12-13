<?php

namespace YlsIdeas\CockroachDb;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Grammar;
use Illuminate\Database\PDO\PostgresDriver;
use Illuminate\Database\PostgresConnection;
use Illuminate\Filesystem\Filesystem;
use YlsIdeas\CockroachDb\Builder\CockroachDbBuilder;
use YlsIdeas\CockroachDb\Processor\CockroachDbProcessor;
use YlsIdeas\CockroachDb\Query\CockroachGrammar as QueryGrammar;
use YlsIdeas\CockroachDb\Schema\CockroachGrammar as SchemaGrammar;
use YlsIdeas\CockroachDb\Schema\CockroachSchemaState;

class CockroachDbConnection extends PostgresConnection implements ConnectionInterface
{
    /**
     * Get the default query grammar instance.
     *
     * @return Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar());
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Illuminate\Database\Schema\PostgresBuilder
     */
    public function getSchemaBuilder()
    {
        if ($this->schemaGrammar === null) {
            $this->useDefaultSchemaGrammar();
        }

        return new CockroachDbBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return Grammar
     */
    protected function getDefaultSchemaGrammar(): Grammar
    {
        return $this->withTablePrefix(new SchemaGrammar());
    }

    /**
     * Get the schema state for the connection.
     *
     * @param  \Illuminate\Filesystem\Filesystem|null  $files
     * @param  callable|null  $processFactory
     * @return \YlsIdeas\CockroachDb\Schema\CockroachSchemaState
     */
    public function getSchemaState(Filesystem $files = null, callable $processFactory = null): CockroachSchemaState
    {
        return new CockroachSchemaState($this, $files, $processFactory);
    }

    /**
     * Get the default post processor instance.
     *
     * @return CockroachDbProcessor
     */
    protected function getDefaultPostProcessor(): CockroachDbProcessor
    {
        return new CockroachDbProcessor();
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

<?php

namespace YlsIdeas\CockroachDb\Schema;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Support\Fluent;
use YlsIdeas\CockroachDb\Exceptions\FeatureNotSupportedException;

class CockroachGrammar extends PostgresGrammar
{
    /**
     * Compile a fulltext index key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     *
     * @throws \RuntimeException
     */
    public function compileFulltext(Blueprint $blueprint, Fluent $command)
    {
        throw new FeatureNotSupportedException('Fulltext indexes are not supported by CockroachDB as of version 2.5');
    }

    /**
     * Compile a drop fulltext index command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropFullText(Blueprint $blueprint, Fluent $command)
    {
        return $this->compileDropIndex($blueprint, $command);
    }

    /**
     * Compile a drop unique key command.
     *
     * CockroachDB doesn't support alter table for dropping unique indexes.
     * https://github.com/cockroachdb/cockroach/issues/42840?version=v22.1
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropUnique(Blueprint $blueprint, Fluent $command)
    {
        $index = $this->wrap($command->get('index'));

        return "drop index {$this->wrapTable($blueprint)}@{$index} cascade";
    }
}

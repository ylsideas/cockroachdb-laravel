<?php

namespace YlsIdeas\CockroachDb\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use YlsIdeas\CockroachDb\Exceptions\FeatureNotSupportedException;

class CockroachGrammar extends PostgresGrammar
{
    /**
     * Compile an update statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @return string
     */
    public function compileUpdate(Builder $query, array $values): string
    {
        if (! empty($query->joins)) {
            $statement = parent::compileUpdateFrom($query, $values);
        } else {
            $statement = Grammar::compileUpdate($query, $values);
        }

        if ($query->orders) {
            $statement .= ' '.$this->compileOrders($query, $query->orders);
        }
        if ($query->limit) {
            $statement .= ' '.$this->compileLimit($query, $query->limit);
        }

        return $statement;
    }

    /**
     * Compile a delete statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    public function compileDelete(Builder $query)
    {
        $table = $this->wrapTable($query->from);

        $where = $this->compileWheres($query);

        if (! empty($query->joins)) {
            throw new FeatureNotSupportedException(
                'Joins for deletions are not supported by CockroachDB, consider using a where in sub-query instead.'
            );
        }

        $statement = "delete from {$table} {$where}";
        if ($query->orders) {
            $statement .= ' '.$this->compileOrders($query, $query->orders);
        }
        if ($query->limit) {
            $statement .= ' '.$this->compileLimit($query, $query->limit);
        }

        return trim($statement);
    }
    
    /**
     * Compile a truncate table statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return array
     */
    public function compileTruncate(Builder $query)
    {
        return ['truncate '.$this->wrapTable($query->from).' cascade' => []];
    }

    /**
     * Compile a "where fulltext" clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    public function whereFullText(Builder $query, $where)
    {
        throw new FeatureNotSupportedException('Fulltext indexes are not supported by CockroachDB as of version 2.5');
    }
}

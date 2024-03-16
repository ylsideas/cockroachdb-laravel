<?php

namespace YlsIdeas\CockroachDb\Schema;

use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\Identifier;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;
use Doctrine\DBAL\Schema\Table;

class CockroachSchemaManager extends PostgreSQLSchemaManager
{
    public function introspectTable(string $table): Table
    {
        // It's possible that users may be using a pre doctrine\dbal 3.5 version.
        // In the event this is the case we warn the user that using the SchemaManager will fail.
        if (is_callable('parent::introspectTable')) {
            return parent::introspectTable($table);
        }

        throw new \RuntimeException('Method introspectTable() not implemented. You need to update doctrine\dbal to ^3.5');
    }

    protected function selectTableColumns(string $databaseName, ?string $tableName = null): Result
    {
        $sql = 'SELECT';

        if ($tableName === null) {
            $sql .= ' c.relname AS table_name, n.nspname AS schema_name,';
        }

        $sql .= <<<'SQL'
                        a.attnum,
                        quote_ident(a.attname) AS field,
                        t.typname AS type,
                        format_type(a.atttypid, a.atttypmod) AS complete_type,
                        (SELECT tc.collcollate FROM pg_catalog.pg_collation tc WHERE tc.oid = a.attcollation) AS collation,
                        (SELECT t1.typname FROM pg_catalog.pg_type t1 WHERE t1.oid = t.typbasetype) AS domain_type,
                        (SELECT format_type(t2.typbasetype, t2.typtypmod) FROM
                          pg_catalog.pg_type t2 WHERE t2.typtype = 'd' AND t2.oid = a.atttypid) AS domain_complete_type,
                        a.attnotnull AS isnotnull,
                        (SELECT 't'
                         FROM pg_index
                         WHERE c.oid = pg_index.indrelid
                            AND pg_index.indkey[0] = a.attnum
                            AND pg_index.indisprimary = 't'
                        ) AS pri,
                        (SELECT pg_get_expr(adbin, adrelid)
                         FROM pg_attrdef
                         WHERE c.oid = pg_attrdef.adrelid
                            AND pg_attrdef.adnum=a.attnum
                        ) AS default,
                        (SELECT pg_description.description
                            FROM pg_description WHERE pg_description.objoid = c.oid AND a.attnum = pg_description.objsubid
                        ) AS comment
                        FROM pg_attribute a
                            INNER JOIN pg_class c
                                ON c.oid = a.attrelid
                            INNER JOIN pg_type t
                                ON t.oid = a.atttypid
                            INNER JOIN pg_namespace n
                                ON n.oid = c.relnamespace
                            LEFT JOIN pg_depend d
                                ON d.objid = c.oid
                                    AND d.deptype = 'e'
                                    AND d.classid = (SELECT oid FROM pg_class WHERE relname = 'pg_class')
            SQL;

        $conditions = array_merge([
            'a.attnum > 0',
            "c.relkind = 'r'",
            'd.refobjid IS NULL',
            'a.attisdropped = false',
        ], $this->buildQueryConditions($tableName));

        $sql .= ' WHERE ' . implode(' AND ', $conditions) . ' ORDER BY a.attnum';

        return $this->_conn->executeQuery($sql);
    }

    /**
     * @param string|null $tableName
     *
     * @return list<string>
     */
    private function buildQueryConditions($tableName): array
    {
        $conditions = [];

        if ($tableName !== null) {
            if (strpos($tableName, '.') !== false) {
                [$schemaName, $tableName] = explode('.', $tableName);
                $conditions[] = 'n.nspname = ' . $this->_platform->quoteStringLiteral($schemaName);
            } else {
                $conditions[] = 'n.nspname = ANY(current_schemas(false))';
            }

            $identifier = new Identifier($tableName);
            $conditions[] = 'c.relname = ' . $this->_platform->quoteStringLiteral($identifier->getName());
        }

        $conditions[] = "n.nspname NOT IN ('pg_catalog', 'information_schema', 'pg_toast')";

        return $conditions;
    }
}

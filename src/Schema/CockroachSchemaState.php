<?php

namespace YlsIdeas\CockroachDb\Schema;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\SchemaState;

class CockroachSchemaState extends SchemaState
{
    /**
     * Dump the database's schema into a file.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  string  $path
     * @return void
     */
    public function dump(Connection $connection, $path)
    {
        $pdo = $connection->getPdo();

        // tables
        $query = $pdo->query("SHOW CREATE ALL TABLES");
        $query->execute();
        file_put_contents($path, $query->fetchAll(\PDO::FETCH_COLUMN));

        // migration statuses
        $query = $pdo->query(sprintf('Select * from "%s"', $this->migrationTable));
        $query->execute();

        $migrations = [];
        while ($migration = $query->fetch(\PDO::FETCH_ASSOC)) {
            $migrations[] = sprintf(
                'Insert into "%s" (%s) values (%s);',
                $this->migrationTable,
                join(
                    ', ',
                    array_map(fn ($value) => '"' . $value . '"', array_keys($migration))
                ),
                join(', ', array_map(fn ($value) => is_string($value) ? "'" . $value . "'" : $value, $migration))
            );
        }

        file_put_contents($path, "\n\n" . join("\n", $migrations) . "\n\n", \FILE_APPEND);
    }

    /**
     * Load the given schema file into the database.
     *
     * @param  string  $path
     * @return void
     */
    public function load($path)
    {
        $pdo = $this->connection->getPdo();
        $pdo->exec(file_get_contents($path));
    }
}

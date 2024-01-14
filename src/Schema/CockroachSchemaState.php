<?php

namespace YlsIdeas\CockroachDb\Schema;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\SchemaState;
use Illuminate\Support\Str;

class CockroachSchemaState extends SchemaState
{
    /**
     * Dump the database's schema into a file.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  string  $path
     * @return void
     */
    public function dump(Connection $connection, $path): void
    {
        $query = $connection->getPdo()->query('SHOW CREATE ALL TABLES');
        $query->execute();

        $file = collect($query->fetchAll(\PDO::FETCH_COLUMN))->join(PHP_EOL);

        $migrationRows = $connection
            ->table($this->migrationTable)
            ->get()
            ->map(fn (\stdClass $row) => (array) $row);

        $statements = $connection->pretend(function (Connection $connection) use ($migrationRows) {
            $connection->table($this->migrationTable)
                ->insert($migrationRows->all());
        });

        if ($statements !== []) {
            $file .= PHP_EOL . $statements[0]['query'];
        }

        $this->files->put($path, $file);
    }

    /**
     * Load the given schema file into the database.
     *
     * @param string $path
     * @return void
     * @throws FileNotFoundException
     */
    public function load($path): void
    {
        $fileContents = $this->files->get($path);
        if ($fileContents === '') {
            throw new \RuntimeException(sprintf('file %s is empty', $path));
        }

        $pdo = $this->connection->getPdo();
        $pdo->exec($fileContents);
    }
}

<?php

namespace YlsIdeas\CockroachDb\Processor;

use Illuminate\Database\Query\Processors\PostgresProcessor;

class CockroachDbProcessor extends PostgresProcessor
{
    public function processColumns($results)
    {
        return array_map(function ($result) {
            $result = (object) $result;

            $autoincrement = $result->default !== null && str_starts_with($result->default, 'nextval(');

            return [
                'name' => $result->name,
                'type_name' => $result->type_name,
                'type' => $result->type,
                'collation' => $result->collation,
                'nullable' => (bool) $result->nullable,
                'default' => ($result->generated ?? null) ? null : $result->default,
                'auto_increment' => $autoincrement,
                'comment' => $result->comment,
                'generation' => ($result->generated ?? null) ? [
                    'type' => match ($result->generated) {
                        's' => 'stored',
                        default => null,
                    },
                    'expression' => $result->default,
                ] : null,
            ];
        }, $results);
    }
}

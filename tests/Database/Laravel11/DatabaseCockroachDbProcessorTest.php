<?php

namespace YlsIdeas\CockroachDb\Tests\Database\Laravel11;

use PHPUnit\Framework\TestCase;
use YlsIdeas\CockroachDb\Processor\CockroachDbProcessor;
use YlsIdeas\CockroachDb\Tests\WithMultipleApplicationVersions;

class DatabaseCockroachDbProcessorTest extends TestCase
{
    use WithMultipleApplicationVersions;

    public function test_process_columns()
    {
        $this->skipIfOlderThan('11.0.0');
        $processor = new CockroachDbProcessor();

        $listing = [
            ['name' => 'id', 'type_name' => 'int4', 'type' => 'integer', 'collation' => '', 'nullable' => true, 'default' => "nextval('employee_id_seq'::regclass)", 'comment' => ''],
            ['name' => 'name', 'type_name' => 'varchar', 'type' => 'character varying(100)', 'collation' => 'collate', 'nullable' => false, 'default' => '', 'comment' => 'foo'],
            ['name' => 'balance', 'type_name' => 'numeric', 'type' => 'numeric(8,2)', 'collation' => '', 'nullable' => true, 'default' => '4', 'comment' => 'NULL'],
            ['name' => 'birth_date', 'type_name' => 'timestamp', 'type' => 'timestamp(6) without time zone', 'collation' => '', 'nullable' => false, 'default' => '', 'comment' => ''],
        ];
        $expected = [
            ['name' => 'id', 'type_name' => 'int4', 'type' => 'integer', 'collation' => '', 'nullable' => true, 'default' => "nextval('employee_id_seq'::regclass)", 'auto_increment' => true, 'comment' => '', 'generation' => null],
            ['name' => 'name', 'type_name' => 'varchar', 'type' => 'character varying(100)', 'collation' => 'collate', 'nullable' => false, 'default' => '', 'auto_increment' => false, 'comment' => 'foo', 'generation' => null],
            ['name' => 'balance', 'type_name' => 'numeric', 'type' => 'numeric(8,2)', 'collation' => '', 'nullable' => true, 'default' => '4', 'auto_increment' => false, 'comment' => 'NULL', 'generation' => null],
            ['name' => 'birth_date', 'type_name' => 'timestamp', 'type' => 'timestamp(6) without time zone', 'collation' => '', 'nullable' => false, 'default' => '', 'auto_increment' => false, 'comment' => '', 'generation' => null],
        ];

        $this->assertEquals($expected, $processor->processColumns($listing));

        // convert listing to objects to simulate PDO::FETCH_CLASS
        foreach ($listing as &$row) {
            $row = (object) $row;
        }

        $this->assertEquals($expected, $processor->processColumns($listing));
    }
}

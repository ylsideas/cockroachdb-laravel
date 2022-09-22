<?php

namespace YlsIdeas\CockroachDb\Tests\Database;

use PHPUnit\Framework\TestCase;
use YlsIdeas\CockroachDb\Processor\CockroachDbProcessor;

class DatabaseCockroachDbProcessorTest extends TestCase
{
    public function test_process_column_listing()
    {
        $processor = new CockroachDbProcessor();

        $listing = [['column_name' => 'id'], ['column_name' => 'name'], ['column_name' => 'email']];
        $expected = ['id', 'name', 'email'];

        $this->assertEquals($expected, $processor->processColumnListing($listing));

        // convert listing to objects to simulate PDO::FETCH_CLASS
        foreach ($listing as &$row) {
            $row = (object) $row;
        }

        $this->assertEquals($expected, $processor->processColumnListing($listing));
    }
}

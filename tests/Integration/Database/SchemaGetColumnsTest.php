<?php

namespace YlsIdeas\CockroachDb\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Builder\CockroachDbBuilder;

class SchemaGetColumnsTest extends DatabaseTestCase
{
    private const TEST_TABLE = 'test_table';

    /**
     * @before
     */
    public function onlyIfGetColumnsExists(): void
    {
        if (! method_exists(CockroachDbBuilder::class, 'getColumns')) {
            $this->markTestSkipped("The Schema::getColumns() function is only available in a later Laravel version.");
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create(self::TEST_TABLE, function (Blueprint $table) {
            $table->id();
            $table->string('column_y');
            $table->string('column_x');
        });
    }

    public function test_schema_get_columns_with_dropped_columns(): void
    {
        Schema::table(self::TEST_TABLE, function (Blueprint $table) {
            $table->dropColumn('column_y');
        });

        $tableColumns = Schema::getColumns(self::TEST_TABLE);
        $this->assertCount(2, $tableColumns);
        $this->assertSame('id', $tableColumns[0]['name']);
        $this->assertSame('column_x', $tableColumns[1]['name']);
    }

    public function test_schema_get_columns_without_dropped_columns(): void
    {
        $tableColumns = Schema::getColumns(self::TEST_TABLE);
        $this->assertCount(3, $tableColumns);
        $this->assertSame('id', $tableColumns[0]['name']);
        $this->assertSame('column_y', $tableColumns[1]['name']);
        $this->assertSame('column_x', $tableColumns[2]['name']);
    }
}

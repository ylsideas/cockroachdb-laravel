<?php

namespace YlsIdeas\CockroachDb\Tests\Integration\Database;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MigrationRenamingTest extends DatabaseTestCase
{
    private const TEST_TABLE = 'test_table';

    protected function setUp(): void
    {
        parent::setUp();

        if (!InstalledVersions::satisfies(new VersionParser(), 'doctrine/dbal', '^3.5')) {
            $this->markTestSkipped(<<<MESSAGE
These tests will always fail under due to pre `doctrine\dbal:3.5` installations missing
Doctrine\DBAL\Schema\PostgreSQLSchemaManager::introspectTable() method.
MESSAGE
            );
        }

        Schema::create(self::TEST_TABLE, function (Blueprint $table) {
            $table->id();
            $table->string('column_y');
            $table->string('column_x');
        });
    }

    public function test_rename_column_to_previously_deleted_one(): void
    {
        Schema::table(self::TEST_TABLE, function (Blueprint $table) {
            $table->dropColumn('column_y');
        });

        Schema::table(self::TEST_TABLE, function (Blueprint $table) {
            $table->renameColumn('column_x', 'column_y');
        });

        $tableColumns = Schema::getColumnListing(self::TEST_TABLE);
        $this->assertCount(2, $tableColumns);
        $this->assertEquals('id', $tableColumns[0]);
        $this->assertEquals('column_y', $tableColumns[1]);
    }

    public function test_rename_column_with_any_previously_deleted_one(): void
    {
        Schema::table(self::TEST_TABLE, function (Blueprint $table) {
            $table->dropColumn('column_y');
        });

        Schema::table(self::TEST_TABLE, function (Blueprint $table) {
            $table->renameColumn('column_x', 'column_z');
        });

        $tableColumns = Schema::getColumnListing(self::TEST_TABLE);
        $this->assertCount(2, $tableColumns);
        $this->assertEquals('id', $tableColumns[0]);
        $this->assertEquals('column_z', $tableColumns[1]);
    }

    public function test_rename_column_without_deleted_ones(): void
    {
        Schema::table(self::TEST_TABLE, function (Blueprint $table) {
            $table->renameColumn('column_x', 'column_z');
        });

        $tableColumns = Schema::getColumnListing(self::TEST_TABLE);
        $this->assertCount(3, $tableColumns);
        $this->assertEquals('id', $tableColumns[0]);
        $this->assertEquals('column_y', $tableColumns[1]);
        $this->assertEquals('column_z', $tableColumns[2]);
    }
}

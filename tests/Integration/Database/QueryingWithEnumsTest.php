<?php

namespace YlsIdeas\CockroachDb\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\WithMultipleApplicationVersions;

if (PHP_VERSION_ID >= 80100) {
    include_once 'Enums.php';
}

/**
 * @requires PHP >= 8.1
 */
class QueryingWithEnumsTest extends DatabaseTestCase
{
    use WithMultipleApplicationVersions;

    public function setUp(): void
    {
        parent::setUp();

        $this->skipIfOlderThan('8.69');
    }

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('enum_casts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('string_status', 100)->nullable();
            $table->integer('integer_status')->nullable();
        });
    }

    public function test_can_query_with_enums()
    {
        DB::table('enum_casts')->insert([
            'string_status' => 'pending',
            'integer_status' => 1,
        ]);

        $record = DB::table('enum_casts')->where('string_status', StringStatus::pending)->first();
        $record2 = DB::table('enum_casts')->where('integer_status', IntegerStatus::pending)->first();
        $record3 = DB::table('enum_casts')->whereIn('integer_status', [IntegerStatus::pending])->first();

        $this->assertNotNull($record);
        $this->assertNotNull($record2);
        $this->assertNotNull($record3);
        $this->assertEquals('pending', $record->string_status);
        $this->assertEquals(1, $record2->integer_status);
    }

    public function test_can_insert_with_enums()
    {
        DB::table('enum_casts')->insert([
            'string_status' => StringStatus::pending,
            'integer_status' => IntegerStatus::pending,
        ]);

        $record = DB::table('enum_casts')->where('string_status', StringStatus::pending)->first();

        $this->assertNotNull($record);
        $this->assertEquals('pending', $record->string_status);
        $this->assertEquals(1, $record->integer_status);
    }
}

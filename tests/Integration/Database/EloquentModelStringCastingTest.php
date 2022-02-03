<?php

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

/**
 * Tests...
 */
test('saving casted attributes to database', function () {
    /** @var \YlsIdeas\CockroachDb\Tests\Integration\Database\StringCasts $model */
    $model = StringCasts::create([
        'array_attributes' => ['key1' => 'value1'],
        'json_attributes' => ['json_key' => 'json_value'],
        'object_attributes' => ['json_key' => 'json_value'],
    ]);
    $this->assertSame(['key1' => 'value1'], $model->getOriginal('array_attributes'));
    $this->assertSame(['key1' => 'value1'], $model->getAttribute('array_attributes'));

    $this->assertSame(['json_key' => 'json_value'], $model->getOriginal('json_attributes'));
    $this->assertSame(['json_key' => 'json_value'], $model->getAttribute('json_attributes'));

    $stdClass = new stdClass();
    $stdClass->json_key = 'json_value';
    $this->assertEquals($stdClass, $model->getOriginal('object_attributes'));
    $this->assertEquals($stdClass, $model->getAttribute('object_attributes'));
});

test('saving casted empty attributes to database', function () {
    /** @var \YlsIdeas\CockroachDb\Tests\Integration\Database\StringCasts $model */
    $model = StringCasts::create([
        'array_attributes' => [],
        'json_attributes' => [],
        'object_attributes' => [],
    ]);
    $this->assertSame([], $model->getOriginal('array_attributes'));
    $this->assertSame([], $model->getAttribute('array_attributes'));

    $this->assertSame([], $model->getOriginal('json_attributes'));
    $this->assertSame([], $model->getAttribute('json_attributes'));

    $this->assertSame([], $model->getOriginal('object_attributes'));
    $this->assertSame([], $model->getAttribute('object_attributes'));
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('casting_table', function (Blueprint $table) {
        $table->increments('id');
        $table->string('array_attributes');
        $table->string('json_attributes');
        $table->string('object_attributes');
        $table->timestamps();
    });
}

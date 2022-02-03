<?php

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
    expect($model->getOriginal('array_attributes'))->toBe(['key1' => 'value1']);
    expect($model->getAttribute('array_attributes'))->toBe(['key1' => 'value1']);

    expect($model->getOriginal('json_attributes'))->toBe(['json_key' => 'json_value']);
    expect($model->getAttribute('json_attributes'))->toBe(['json_key' => 'json_value']);

    $stdClass = new stdClass();
    $stdClass->json_key = 'json_value';
    expect($model->getOriginal('object_attributes'))->toEqual($stdClass);
    expect($model->getAttribute('object_attributes'))->toEqual($stdClass);
});

test('saving casted empty attributes to database', function () {
    /** @var \YlsIdeas\CockroachDb\Tests\Integration\Database\StringCasts $model */
    $model = StringCasts::create([
        'array_attributes' => [],
        'json_attributes' => [],
        'object_attributes' => [],
    ]);
    expect($model->getOriginal('array_attributes'))->toBe([]);
    expect($model->getAttribute('array_attributes'))->toBe([]);

    expect($model->getOriginal('json_attributes'))->toBe([]);
    expect($model->getAttribute('json_attributes'))->toBe([]);

    expect($model->getOriginal('object_attributes'))->toBe([]);
    expect($model->getAttribute('object_attributes'))->toBe([]);
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

<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

test('strings are castable', function () {
    /** @var \YlsIdeas\CockroachDb\Tests\Integration\Database\EloquentModelJsonCastingTest\JsonCast $object */
    $object = JsonCast::create([
        'basic_string_as_json_field' => 'this is a string',
        'json_string_as_json_field' => '{"key1":"value1"}',
    ]);

    expect($object->basic_string_as_json_field)->toBe('this is a string');
    expect($object->json_string_as_json_field)->toBe('{"key1":"value1"}');
});

test('arrays are castable', function () {
    /** @var \YlsIdeas\CockroachDb\Tests\Integration\Database\EloquentModelJsonCastingTest\JsonCast $object */
    $object = JsonCast::create([
        'array_as_json_field' => ['key1' => 'value1'],
    ]);

    expect($object->array_as_json_field)->toEqual(['key1' => 'value1']);
});

test('objects are castable', function () {
    $object = new stdClass();
    $object->key1 = 'value1';

    /** @var \YlsIdeas\CockroachDb\Tests\Integration\Database\EloquentModelJsonCastingTest\JsonCast $user */
    $user = JsonCast::create([
        'object_as_json_field' => $object,
    ]);

    expect($user->object_as_json_field)->toBeInstanceOf(stdClass::class);
    expect($user->object_as_json_field->key1)->toBe('value1');
});

test('collections are castable', function () {
    /** @var \YlsIdeas\CockroachDb\Tests\Integration\Database\EloquentModelJsonCastingTest\JsonCast $user */
    $user = JsonCast::create([
        'collection_as_json_field' => new Collection(['key1' => 'value1']),
    ]);

    expect($user->collection_as_json_field)->toBeInstanceOf(Collection::class);
    expect($user->collection_as_json_field->get('key1'))->toBe('value1');
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('json_casts', function (Blueprint $table) {
        $table->increments('id');
        $table->json('basic_string_as_json_field')->nullable();
        $table->json('json_string_as_json_field')->nullable();
        $table->json('array_as_json_field')->nullable();
        $table->json('object_as_json_field')->nullable();
        $table->json('collection_as_json_field')->nullable();
    });
}

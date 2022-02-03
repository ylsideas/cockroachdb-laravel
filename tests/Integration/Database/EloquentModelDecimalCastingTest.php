<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

test('decimals are castable', function () {
    $user = TestModel1::create([
        'decimal_field_2' => '12',
        'decimal_field_4' => '1234',
    ]);

    expect($user->toArray()['decimal_field_2'])->toBe('12.00');
    expect($user->toArray()['decimal_field_4'])->toBe('1234.0000');

    $user->decimal_field_2 = 12;
    $user->decimal_field_4 = '1234';

    expect($user->toArray()['decimal_field_2'])->toBe('12.00');
    expect($user->toArray()['decimal_field_4'])->toBe('1234.0000');

    expect($user->isDirty())->toBeFalse();

    $user->decimal_field_4 = '1234.1234';
    expect($user->isDirty())->toBeTrue();
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('test_model1', function (Blueprint $table) {
        $table->increments('id');
        $table->decimal('decimal_field_2', 8, 2)->nullable();
        $table->decimal('decimal_field_4', 8, 4)->nullable();
    });
}

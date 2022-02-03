<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

test('dates are immutable castable', function () {
    $model = TestModelImmutable::create([
        'date_field' => '2019-10-01',
        'datetime_field' => '2019-10-01 10:15:20',
    ]);

    expect($model->toArray()['date_field'])->toBe('2019-10-01T00:00:00.000000Z');
    expect($model->toArray()['datetime_field'])->toBe('2019-10-01T10:15:20.000000Z');
    expect($model->date_field)->toBeInstanceOf(CarbonImmutable::class);
    expect($model->datetime_field)->toBeInstanceOf(CarbonImmutable::class);
});

test('dates are immutable and custom castable', function () {
    $model = TestModelCustomImmutable::create([
        'date_field' => '2019-10-01',
        'datetime_field' => '2019-10-01 10:15:20',
    ]);

    expect($model->toArray()['date_field'])->toBe('2019-10');
    expect($model->toArray()['datetime_field'])->toBe('2019-10 10:15');
    expect($model->date_field)->toBeInstanceOf(CarbonImmutable::class);
    expect($model->datetime_field)->toBeInstanceOf(CarbonImmutable::class);
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('test_model_immutable', function (Blueprint $table) {
        $table->increments('id');
        $table->date('date_field')->nullable();
        $table->datetime('datetime_field')->nullable();
    });
}

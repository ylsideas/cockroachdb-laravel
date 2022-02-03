<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

test('dates are immutable castable', function () {
    $model = TestModelImmutable::create([
        'date_field' => '2019-10-01',
        'datetime_field' => '2019-10-01 10:15:20',
    ]);

    $this->assertSame('2019-10-01T00:00:00.000000Z', $model->toArray()['date_field']);
    $this->assertSame('2019-10-01T10:15:20.000000Z', $model->toArray()['datetime_field']);
    $this->assertInstanceOf(CarbonImmutable::class, $model->date_field);
    $this->assertInstanceOf(CarbonImmutable::class, $model->datetime_field);
});

test('dates are immutable and custom castable', function () {
    $model = TestModelCustomImmutable::create([
        'date_field' => '2019-10-01',
        'datetime_field' => '2019-10-01 10:15:20',
    ]);

    $this->assertSame('2019-10', $model->toArray()['date_field']);
    $this->assertSame('2019-10 10:15', $model->toArray()['datetime_field']);
    $this->assertInstanceOf(CarbonImmutable::class, $model->date_field);
    $this->assertInstanceOf(CarbonImmutable::class, $model->datetime_field);
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

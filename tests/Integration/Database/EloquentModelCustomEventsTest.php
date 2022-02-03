<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

beforeEach(function () {
    Event::listen(CustomEvent::class, function () {
        $_SERVER['fired_event'] = true;
    });
});

test('flush listeners clears custom events', function () {
    $_SERVER['fired_event'] = false;

    TestModel1::flushEventListeners();

    TestModel1::create();

    $this->assertFalse($_SERVER['fired_event']);
});

test('custom event listeners are fired', function () {
    $_SERVER['fired_event'] = false;

    TestModel1::create();

    $this->assertTrue($_SERVER['fired_event']);
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('test_model1', function (Blueprint $table) {
        $table->increments('id');
    });
}

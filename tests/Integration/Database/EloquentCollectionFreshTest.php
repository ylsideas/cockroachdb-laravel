<?php

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\Fixtures\User;

uses(DatabaseTestCase::class);

test('eloquent collection fresh', function () {
    User::insert([
        ['email' => 'laravel@framework.com'],
        ['email' => 'laravel@laravel.com'],
    ]);

    $collection = User::all();

    $collection->first()->delete();

    $freshCollection = $collection->fresh();

    $this->assertCount(1, $freshCollection);
    $this->assertInstanceOf(EloquentCollection::class, $freshCollection);
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('email');
    });
}

<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

it('only eager loads required models', function () {
    $this->retrievedLogins = 0;
    User::getEventDispatcher()->listen('eloquent.retrieved:*', function ($event, $models) {
        foreach ($models as $model) {
            if (get_class($model) == Login::class) {
                $this->retrievedLogins++;
            }
        }
    });

    $user = User::create();
    $user->latest_login()->create();
    $user->latest_login()->create();
    $user = User::create();
    $user->latest_login()->create();
    $user->latest_login()->create();

    User::with('latest_login')->get();

    expect($this->retrievedLogins)->toBe(2);
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('users', function ($table) {
        $table->id();
    });

    Schema::create('logins', function ($table) {
        $table->id();
        $table->foreignId('user_id');
    });
}

function latest_login()
{
    return test()->hasOne(Login::class)->ofMany();
}

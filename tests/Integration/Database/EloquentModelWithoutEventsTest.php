<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

test('without events registers booted listeners for later', function () {
    $model = AutoFilledModel::withoutEvents(function () {
        return AutoFilledModel::create();
    });

    $this->assertNull($model->project);

    $model->save();

    $this->assertSame('Laravel', $model->project);
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('auto_filled_models', function (Blueprint $table) {
        $table->increments('id');
        $table->text('project')->nullable();
    });
}

function boot()
{
    parent::boot();

    static::saving(function ($model) {
        $model->project = 'Laravel';
    });
}

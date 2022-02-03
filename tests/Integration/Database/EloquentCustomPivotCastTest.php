<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

test('casts are respected on attach', function () {
    $user = CustomPivotCastTestUser::forceCreate([
        'email' => 'taylor@laravel.com',
    ]);

    $project = CustomPivotCastTestProject::forceCreate([
        'name' => 'Test Project',
    ]);

    $project->collaborators()->attach($user, ['permissions' => ['foo' => 'bar']]);
    $project = $project->fresh();

    expect($project->collaborators[0]->pivot->permissions)->toEqual(['foo' => 'bar']);
});

test('casts are respected on attach array', function () {
    $user = CustomPivotCastTestUser::forceCreate([
        'email' => 'taylor@laravel.com',
    ]);

    $user2 = CustomPivotCastTestUser::forceCreate([
        'email' => 'mohamed@laravel.com',
    ]);

    $project = CustomPivotCastTestProject::forceCreate([
        'name' => 'Test Project',
    ]);

    $project->collaborators()->attach([
        $user->id => ['permissions' => ['foo' => 'bar']],
        $user2->id => ['permissions' => ['baz' => 'bar']],
    ]);
    $project = $project->fresh();

    expect($project->collaborators[0]->pivot->permissions)->toEqual(['foo' => 'bar']);
    expect($project->collaborators[1]->pivot->permissions)->toEqual(['baz' => 'bar']);
});

test('casts are respected on sync', function () {
    $user = CustomPivotCastTestUser::forceCreate([
        'email' => 'taylor@laravel.com',
    ]);

    $project = CustomPivotCastTestProject::forceCreate([
        'name' => 'Test Project',
    ]);

    $project->collaborators()->sync([$user->id => ['permissions' => ['foo' => 'bar']]]);
    $project = $project->fresh();

    expect($project->collaborators[0]->pivot->permissions)->toEqual(['foo' => 'bar']);
});

test('casts are respected on sync array', function () {
    $user = CustomPivotCastTestUser::forceCreate([
        'email' => 'taylor@laravel.com',
    ]);

    $user2 = CustomPivotCastTestUser::forceCreate([
        'email' => 'mohamed@laravel.com',
    ]);

    $project = CustomPivotCastTestProject::forceCreate([
        'name' => 'Test Project',
    ]);

    $project->collaborators()->sync([
        $user->id => ['permissions' => ['foo' => 'bar']],
        $user2->id => ['permissions' => ['baz' => 'bar']],
    ]);
    $project = $project->fresh();

    expect($project->collaborators[0]->pivot->permissions)->toEqual(['foo' => 'bar']);
    expect($project->collaborators[1]->pivot->permissions)->toEqual(['baz' => 'bar']);
});

test('casts are respected on sync array while updating existing', function () {
    $user = CustomPivotCastTestUser::forceCreate([
        'email' => 'taylor@laravel.com',
    ]);

    $user2 = CustomPivotCastTestUser::forceCreate([
        'email' => 'mohamed@laravel.com',
    ]);

    $project = CustomPivotCastTestProject::forceCreate([
        'name' => 'Test Project',
    ]);

    $project->collaborators()->attach([
        $user->id => ['permissions' => ['foo' => 'bar']],
        $user2->id => ['permissions' => ['baz' => 'bar']],
    ]);

    $project->collaborators()->sync([
        $user->id => ['permissions' => ['foo1' => 'bar1']],
        $user2->id => ['permissions' => ['baz2' => 'bar2']],
    ]);

    $project = $project->fresh();

    expect($project->collaborators[0]->pivot->permissions)->toEqual(['foo1' => 'bar1']);
    expect($project->collaborators[1]->pivot->permissions)->toEqual(['baz2' => 'bar2']);
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('email');
    });

    Schema::create('projects', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
    });

    Schema::create('project_users', function (Blueprint $table) {
        $table->integer('user_id');
        $table->integer('project_id');
        $table->text('permissions');
    });
}

function collaborators()
{
    return test()->belongsToMany(
        CustomPivotCastTestUser::class,
        'project_users',
        'project_id',
        'user_id'
    )->using(CustomPivotCastTestCollaborator::class)->withPivot('permissions');
}

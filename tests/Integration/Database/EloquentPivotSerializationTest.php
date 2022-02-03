<?php

use Illuminate\Database\Eloquent\Collection as DatabaseCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

test('pivot can be serialized and restored', function () {
    $user = PivotSerializationTestUser::forceCreate(['email' => 'taylor@laravel.com']);
    $project = PivotSerializationTestProject::forceCreate(['name' => 'Test Project']);
    $project->collaborators()->attach($user);

    $project = $project->fresh();

    $class = new PivotSerializationTestClass($project->collaborators->first()->pivot);
    $class = unserialize(serialize($class));

    $this->assertEquals($project->collaborators->first()->pivot->user_id, $class->pivot->user_id);
    $this->assertEquals($project->collaborators->first()->pivot->project_id, $class->pivot->project_id);

    $class->pivot->save();
});

test('morph pivot can be serialized and restored', function () {
    $project = PivotSerializationTestProject::forceCreate(['name' => 'Test Project']);
    $tag = PivotSerializationTestTag::forceCreate(['name' => 'Test Tag']);
    $project->tags()->attach($tag);

    $project = $project->fresh();

    $class = new PivotSerializationTestClass($project->tags->first()->pivot);
    $class = unserialize(serialize($class));

    $this->assertEquals($project->tags->first()->pivot->tag_id, $class->pivot->tag_id);
    $this->assertEquals($project->tags->first()->pivot->taggable_id, $class->pivot->taggable_id);
    $this->assertEquals($project->tags->first()->pivot->taggable_type, $class->pivot->taggable_type);

    $class->pivot->save();
});

test('collection of pivots can be serialized and restored', function () {
    $user = PivotSerializationTestUser::forceCreate(['email' => 'taylor@laravel.com']);
    $user2 = PivotSerializationTestUser::forceCreate(['email' => 'mohamed@laravel.com']);
    $project = PivotSerializationTestProject::forceCreate(['name' => 'Test Project']);

    $project->collaborators()->attach($user);
    $project->collaborators()->attach($user2);

    $project = $project->fresh();

    $class = new PivotSerializationTestCollectionClass(DatabaseCollection::make($project->collaborators->map->pivot));
    $class = unserialize(serialize($class));

    $this->assertEquals($project->collaborators[0]->pivot->user_id, $class->pivots[0]->user_id);
    $this->assertEquals($project->collaborators[1]->pivot->project_id, $class->pivots[1]->project_id);
});

test('collection of morph pivots can be serialized and restored', function () {
    $tag = PivotSerializationTestTag::forceCreate(['name' => 'Test Tag 1']);
    $tag2 = PivotSerializationTestTag::forceCreate(['name' => 'Test Tag 2']);
    $project = PivotSerializationTestProject::forceCreate(['name' => 'Test Project']);

    $project->tags()->attach($tag);
    $project->tags()->attach($tag2);

    $project = $project->fresh();

    $class = new PivotSerializationTestCollectionClass(DatabaseCollection::make($project->tags->map->pivot));
    $class = unserialize(serialize($class));

    $this->assertEquals($project->tags[0]->pivot->tag_id, $class->pivots[0]->tag_id);
    $this->assertEquals($project->tags[0]->pivot->taggable_id, $class->pivots[0]->taggable_id);
    $this->assertEquals($project->tags[0]->pivot->taggable_type, $class->pivots[0]->taggable_type);

    $this->assertEquals($project->tags[1]->pivot->tag_id, $class->pivots[1]->tag_id);
    $this->assertEquals($project->tags[1]->pivot->taggable_id, $class->pivots[1]->taggable_id);
    $this->assertEquals($project->tags[1]->pivot->taggable_type, $class->pivots[1]->taggable_type);
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('email');
        $table->timestamps();
    });

    Schema::create('projects', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->timestamps();
    });

    Schema::create('project_users', function (Blueprint $table) {
        $table->integer('user_id');
        $table->integer('project_id');
    });

    Schema::create('tags', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->timestamps();
    });

    Schema::create('taggables', function (Blueprint $table) {
        $table->integer('tag_id');
        $table->integer('taggable_id');
        $table->string('taggable_type');
    });
}

function __construct($pivots)
{
    test()->pivots = $pivots;
}

function collaborators()
{
    return test()->belongsToMany(
        PivotSerializationTestUser::class,
        'project_users',
        'project_id',
        'user_id'
    )->using(PivotSerializationTestCollaborator::class);
}

function tags()
{
    return test()->morphToMany(PivotSerializationTestTag::class, 'taggable', 'taggables', 'taggable_id', 'tag_id')
            ->using(PivotSerializationTestTagAttachment::class);
}

function projects()
{
    return test()->morphedByMany(PivotSerializationTestProject::class, 'taggable', 'taggables', 'tag_id', 'taggable_id')
                ->using(PivotSerializationTestTagAttachment::class);
}

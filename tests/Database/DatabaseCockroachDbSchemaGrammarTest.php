<?php

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ForeignIdColumnDefinition;
use Mockery as m;

use YlsIdeas\CockroachDb\Schema\CockroachGrammar;

afterEach(function () {
    m::close();
});

test('basic create table', function () {
    $blueprint = new Blueprint('users');
    $blueprint->create();
    $blueprint->increments('id');
    $blueprint->string('email');
    $blueprint->string('name')->collation('nb_NO.utf8');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('create table "users" ("id" serial primary key not null, "email" varchar(255) not null, "name" varchar(255) collate "nb_NO.utf8" not null)');

    $blueprint = new Blueprint('users');
    $blueprint->increments('id');
    $blueprint->string('email');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "id" serial primary key not null, add column "email" varchar(255) not null');
});

test('create table with auto increment starting value', function () {
    $blueprint = new Blueprint('users');
    $blueprint->create();
    $blueprint->increments('id')->startingValue(1000);
    $blueprint->string('email');
    $blueprint->string('name')->collation('nb_NO.utf8');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(2);
    expect($statements[0])->toBe('create table "users" ("id" serial primary key not null, "email" varchar(255) not null, "name" varchar(255) collate "nb_NO.utf8" not null)');
    expect($statements[1])->toBe('alter sequence users_id_seq restart with 1000');
});

test('create table and comment column', function () {
    $blueprint = new Blueprint('users');
    $blueprint->create();
    $blueprint->increments('id');
    $blueprint->string('email')->comment('my first comment');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(2);
    expect($statements[0])->toBe('create table "users" ("id" serial primary key not null, "email" varchar(255) not null)');
    expect($statements[1])->toBe('comment on column "users"."email" is \'my first comment\'');
});

test('create temporary table', function () {
    $blueprint = new Blueprint('users');
    $blueprint->create();
    $blueprint->temporary();
    $blueprint->increments('id');
    $blueprint->string('email');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('create temporary table "users" ("id" serial primary key not null, "email" varchar(255) not null)');
});

test('drop table', function () {
    $blueprint = new Blueprint('users');
    $blueprint->drop();
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('drop table "users"');
});

test('drop table if exists', function () {
    $blueprint = new Blueprint('users');
    $blueprint->dropIfExists();
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('drop table if exists "users"');
});

test('drop column', function () {
    $blueprint = new Blueprint('users');
    $blueprint->dropColumn('foo');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" drop column "foo"');

    $blueprint = new Blueprint('users');
    $blueprint->dropColumn(['foo', 'bar']);
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" drop column "foo", drop column "bar"');

    $blueprint = new Blueprint('users');
    $blueprint->dropColumn('foo', 'bar');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" drop column "foo", drop column "bar"');
});

test('drop primary', function () {
    $blueprint = new Blueprint('users');
    $blueprint->dropPrimary();
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" drop constraint "users_pkey"');
});

test('drop unique', function () {
    $blueprint = new Blueprint('users');
    $blueprint->dropUnique('foo');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" drop constraint "foo"');
});

test('drop index', function () {
    $blueprint = new Blueprint('users');
    $blueprint->dropIndex('foo');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('drop index "foo"');
});

test('drop spatial index', function () {
    $blueprint = new Blueprint('geo');
    $blueprint->dropSpatialIndex(['coordinates']);
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('drop index "geo_coordinates_spatialindex"');
});

test('drop foreign', function () {
    $blueprint = new Blueprint('users');
    $blueprint->dropForeign('foo');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" drop constraint "foo"');
});

test('drop timestamps', function () {
    $blueprint = new Blueprint('users');
    $blueprint->dropTimestamps();
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" drop column "created_at", drop column "updated_at"');
});

test('drop timestamps tz', function () {
    $blueprint = new Blueprint('users');
    $blueprint->dropTimestampsTz();
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" drop column "created_at", drop column "updated_at"');
});

test('drop morphs', function () {
    $blueprint = new Blueprint('photos');
    $blueprint->dropMorphs('imageable');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(2);
    expect($statements[0])->toBe('drop index "photos_imageable_type_imageable_id_index"');
    expect($statements[1])->toBe('alter table "photos" drop column "imageable_type", drop column "imageable_id"');
});

test('rename table', function () {
    $blueprint = new Blueprint('users');
    $blueprint->rename('foo');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" rename to "foo"');
});

test('rename index', function () {
    $blueprint = new Blueprint('users');
    $blueprint->renameIndex('foo', 'bar');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter index "foo" rename to "bar"');
});

test('adding primary key', function () {
    $blueprint = new Blueprint('users');
    $blueprint->primary('foo');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add primary key ("foo")');
});

test('adding unique key', function () {
    $blueprint = new Blueprint('users');
    $blueprint->unique('foo', 'bar');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add constraint "bar" unique ("foo")');
});

test('adding index', function () {
    $blueprint = new Blueprint('users');
    $blueprint->index(['foo', 'bar'], 'baz');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('create index "baz" on "users" ("foo", "bar")');
});

test('adding index with algorithm', function () {
    $blueprint = new Blueprint('users');
    $blueprint->index(['foo', 'bar'], 'baz', 'hash');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('create index "baz" on "users" using hash ("foo", "bar")');
});

test('adding spatial index', function () {
    $blueprint = new Blueprint('geo');
    $blueprint->spatialIndex('coordinates');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('create index "geo_coordinates_spatialindex" on "geo" using gist ("coordinates")');
});

test('adding fluent spatial index', function () {
    $blueprint = new Blueprint('geo');
    $blueprint->point('coordinates')->spatialIndex();
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(2);
    expect($statements[1])->toBe('create index "geo_coordinates_spatialindex" on "geo" using gist ("coordinates")');
});

test('adding raw index', function () {
    $blueprint = new Blueprint('users');
    $blueprint->rawIndex('(function(column))', 'raw_index');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('create index "raw_index" on "users" ((function(column)))');
});

test('adding incrementing i d', function () {
    $blueprint = new Blueprint('users');
    $blueprint->increments('id');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "id" serial primary key not null');
});

test('adding small incrementing i d', function () {
    $blueprint = new Blueprint('users');
    $blueprint->smallIncrements('id');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "id" smallserial primary key not null');
});

test('adding medium incrementing i d', function () {
    $blueprint = new Blueprint('users');
    $blueprint->mediumIncrements('id');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "id" serial primary key not null');
});

test('adding i d', function () {
    $blueprint = new Blueprint('users');
    $blueprint->id();
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "id" bigserial primary key not null');

    $blueprint = new Blueprint('users');
    $blueprint->id('foo');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" bigserial primary key not null');
});

test('adding foreign i d', function () {
    $blueprint = new Blueprint('users');
    $foreignId = $blueprint->foreignId('foo');
    $blueprint->foreignId('company_id')->constrained();
    $blueprint->foreignId('laravel_idea_id')->constrained();
    $blueprint->foreignId('team_id')->references('id')->on('teams');
    $blueprint->foreignId('team_column_id')->constrained('teams');

    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($foreignId)->toBeInstanceOf(ForeignIdColumnDefinition::class);
    $this->assertSame([
        'alter table "users" add column "foo" bigint not null, add column "company_id" bigint not null, add column "laravel_idea_id" bigint not null, add column "team_id" bigint not null, add column "team_column_id" bigint not null',
        'alter table "users" add constraint "users_company_id_foreign" foreign key ("company_id") references "companies" ("id")',
        'alter table "users" add constraint "users_laravel_idea_id_foreign" foreign key ("laravel_idea_id") references "laravel_ideas" ("id")',
        'alter table "users" add constraint "users_team_id_foreign" foreign key ("team_id") references "teams" ("id")',
        'alter table "users" add constraint "users_team_column_id_foreign" foreign key ("team_column_id") references "teams" ("id")',
    ], $statements);
});

test('adding big incrementing i d', function () {
    $blueprint = new Blueprint('users');
    $blueprint->bigIncrements('id');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "id" bigserial primary key not null');
});

test('adding string', function () {
    $blueprint = new Blueprint('users');
    $blueprint->string('foo');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" varchar(255) not null');

    $blueprint = new Blueprint('users');
    $blueprint->string('foo', 100);
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" varchar(100) not null');

    $blueprint = new Blueprint('users');
    $blueprint->string('foo', 100)->nullable()->default('bar');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" varchar(100) null default \'bar\'');
});

test('adding text', function () {
    $blueprint = new Blueprint('users');
    $blueprint->text('foo');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" text not null');
});

test('adding big integer', function () {
    $blueprint = new Blueprint('users');
    $blueprint->bigInteger('foo');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" bigint not null');

    $blueprint = new Blueprint('users');
    $blueprint->bigInteger('foo', true);
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" bigserial primary key not null');
});

test('adding integer', function () {
    $blueprint = new Blueprint('users');
    $blueprint->integer('foo');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" integer not null');

    $blueprint = new Blueprint('users');
    $blueprint->integer('foo', true);
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" serial primary key not null');
});

test('adding medium integer', function () {
    $blueprint = new Blueprint('users');
    $blueprint->mediumInteger('foo');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" integer not null');

    $blueprint = new Blueprint('users');
    $blueprint->mediumInteger('foo', true);
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" serial primary key not null');
});

test('adding tiny integer', function () {
    $blueprint = new Blueprint('users');
    $blueprint->tinyInteger('foo');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" smallint not null');

    $blueprint = new Blueprint('users');
    $blueprint->tinyInteger('foo', true);
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" smallserial primary key not null');
});

test('adding small integer', function () {
    $blueprint = new Blueprint('users');
    $blueprint->smallInteger('foo');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" smallint not null');

    $blueprint = new Blueprint('users');
    $blueprint->smallInteger('foo', true);
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" smallserial primary key not null');
});

test('adding float', function () {
    $blueprint = new Blueprint('users');
    $blueprint->float('foo', 5, 2);
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" double precision not null');
});

test('adding double', function () {
    $blueprint = new Blueprint('users');
    $blueprint->double('foo', 15, 8);
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" double precision not null');
});

test('adding decimal', function () {
    $blueprint = new Blueprint('users');
    $blueprint->decimal('foo', 5, 2);
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" decimal(5, 2) not null');
});

test('adding boolean', function () {
    $blueprint = new Blueprint('users');
    $blueprint->boolean('foo');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" boolean not null');
});

test('adding enum', function () {
    $blueprint = new Blueprint('users');
    $blueprint->enum('role', ['member', 'admin']);
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "role" varchar(255) check ("role" in (\'member\', \'admin\')) not null');
});

test('adding date', function () {
    $blueprint = new Blueprint('users');
    $blueprint->date('foo');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" date not null');
});

test('adding year', function () {
    $blueprint = new Blueprint('users');
    $blueprint->year('birth_year');
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "birth_year" integer not null');
});

test('adding json', function () {
    $blueprint = new Blueprint('users');
    $blueprint->json('foo');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" json not null');
});

test('adding jsonb', function () {
    $blueprint = new Blueprint('users');
    $blueprint->jsonb('foo');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" jsonb not null');
});

test('adding date time', function () {
    $blueprint = new Blueprint('users');
    $blueprint->dateTime('created_at');
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "created_at" timestamp(0) without time zone not null');
});

test('adding date time with precision', function () {
    $blueprint = new Blueprint('users');
    $blueprint->dateTime('created_at', 1);
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "created_at" timestamp(1) without time zone not null');
});

test('adding date time with null precision', function () {
    $blueprint = new Blueprint('users');
    $blueprint->dateTime('created_at', null);
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "created_at" timestamp without time zone not null');
});

test('adding date time tz', function () {
    $blueprint = new Blueprint('users');
    $blueprint->dateTimeTz('created_at');
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "created_at" timestamp(0) with time zone not null');
});

test('adding date time tz with precision', function () {
    $blueprint = new Blueprint('users');
    $blueprint->dateTimeTz('created_at', 1);
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "created_at" timestamp(1) with time zone not null');
});

test('adding date time tz with null precision', function () {
    $blueprint = new Blueprint('users');
    $blueprint->dateTimeTz('created_at', null);
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "created_at" timestamp with time zone not null');
});

test('adding time', function () {
    $blueprint = new Blueprint('users');
    $blueprint->time('created_at');
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "created_at" time(0) without time zone not null');
});

test('adding time with precision', function () {
    $blueprint = new Blueprint('users');
    $blueprint->time('created_at', 1);
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "created_at" time(1) without time zone not null');
});

test('adding time with null precision', function () {
    $blueprint = new Blueprint('users');
    $blueprint->time('created_at', null);
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "created_at" time without time zone not null');
});

test('adding time tz', function () {
    $blueprint = new Blueprint('users');
    $blueprint->timeTz('created_at');
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "created_at" time(0) with time zone not null');
});

test('adding time tz with precision', function () {
    $blueprint = new Blueprint('users');
    $blueprint->timeTz('created_at', 1);
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "created_at" time(1) with time zone not null');
});

test('adding time tz with null precision', function () {
    $blueprint = new Blueprint('users');
    $blueprint->timeTz('created_at', null);
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "created_at" time with time zone not null');
});

test('adding timestamp', function () {
    $blueprint = new Blueprint('users');
    $blueprint->timestamp('created_at');
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "created_at" timestamp(0) without time zone not null');
});

test('adding timestamp with precision', function () {
    $blueprint = new Blueprint('users');
    $blueprint->timestamp('created_at', 1);
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "created_at" timestamp(1) without time zone not null');
});

test('adding timestamp with null precision', function () {
    $blueprint = new Blueprint('users');
    $blueprint->timestamp('created_at', null);
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "created_at" timestamp without time zone not null');
});

test('adding timestamp tz', function () {
    $blueprint = new Blueprint('users');
    $blueprint->timestampTz('created_at');
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "created_at" timestamp(0) with time zone not null');
});

test('adding timestamp tz with precision', function () {
    $blueprint = new Blueprint('users');
    $blueprint->timestampTz('created_at', 1);
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "created_at" timestamp(1) with time zone not null');
});

test('adding timestamp tz with null precision', function () {
    $blueprint = new Blueprint('users');
    $blueprint->timestampTz('created_at', null);
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "created_at" timestamp with time zone not null');
});

test('adding timestamps', function () {
    $blueprint = new Blueprint('users');
    $blueprint->timestamps();
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "created_at" timestamp(0) without time zone null, add column "updated_at" timestamp(0) without time zone null');
});

test('adding timestamps tz', function () {
    $blueprint = new Blueprint('users');
    $blueprint->timestampsTz();
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "created_at" timestamp(0) with time zone null, add column "updated_at" timestamp(0) with time zone null');
});

test('adding binary', function () {
    $blueprint = new Blueprint('users');
    $blueprint->binary('foo');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" bytea not null');
});

test('adding uuid', function () {
    $blueprint = new Blueprint('users');
    $blueprint->uuid('foo');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" uuid not null');
});

test('adding foreign uuid', function () {
    $blueprint = new Blueprint('users');
    $foreignUuid = $blueprint->foreignUuid('foo');
    $blueprint->foreignUuid('company_id')->constrained();
    $blueprint->foreignUuid('laravel_idea_id')->constrained();
    $blueprint->foreignUuid('team_id')->references('id')->on('teams');
    $blueprint->foreignUuid('team_column_id')->constrained('teams');

    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($foreignUuid)->toBeInstanceOf(ForeignIdColumnDefinition::class);
    $this->assertSame([
        'alter table "users" add column "foo" uuid not null, add column "company_id" uuid not null, add column "laravel_idea_id" uuid not null, add column "team_id" uuid not null, add column "team_column_id" uuid not null',
        'alter table "users" add constraint "users_company_id_foreign" foreign key ("company_id") references "companies" ("id")',
        'alter table "users" add constraint "users_laravel_idea_id_foreign" foreign key ("laravel_idea_id") references "laravel_ideas" ("id")',
        'alter table "users" add constraint "users_team_id_foreign" foreign key ("team_id") references "teams" ("id")',
        'alter table "users" add constraint "users_team_column_id_foreign" foreign key ("team_column_id") references "teams" ("id")',
    ], $statements);
});

test('adding generated as', function () {
    $blueprint = new Blueprint('users');
    $blueprint->increments('foo')->generatedAs();
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" integer generated by default as identity primary key not null');
    // With always modifier
    $blueprint = new Blueprint('users');
    $blueprint->increments('foo')->generatedAs()->always();
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" integer generated always as identity primary key not null');
    // With sequence options
    $blueprint = new Blueprint('users');
    $blueprint->increments('foo')->generatedAs('increment by 10 start with 100');
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" integer generated by default as identity (increment by 10 start with 100) primary key not null');
    // Not a primary key
    $blueprint = new Blueprint('users');
    $blueprint->integer('foo')->generatedAs();
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" integer generated by default as identity not null');
});

test('adding virtual as', function () {
    $blueprint = new Blueprint('users');
    $blueprint->integer('foo')->nullable();
    $blueprint->boolean('bar')->virtualAs('foo is not null');
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" integer null, add column "bar" boolean not null generated always as (foo is not null)');
});

test('adding stored as', function () {
    $blueprint = new Blueprint('users');
    $blueprint->integer('foo')->nullable();
    $blueprint->boolean('bar')->storedAs('foo is not null');
    $statements = $blueprint->toSql(getConnection(), getGrammar());
    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" integer null, add column "bar" boolean not null generated always as (foo is not null) stored');
});

test('adding ip address', function () {
    $blueprint = new Blueprint('users');
    $blueprint->ipAddress('foo');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" inet not null');
});

test('adding mac address', function () {
    $blueprint = new Blueprint('users');
    $blueprint->macAddress('foo');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add column "foo" macaddr not null');
});

test('compile foreign', function () {
    $blueprint = new Blueprint('users');
    $blueprint->foreign('parent_id')->references('id')->on('parents')->onDelete('cascade')->deferrable();
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add constraint "users_parent_id_foreign" foreign key ("parent_id") references "parents" ("id") on delete cascade deferrable');

    $blueprint = new Blueprint('users');
    $blueprint->foreign('parent_id')->references('id')->on('parents')->onDelete('cascade')->deferrable(false)->initiallyImmediate();
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add constraint "users_parent_id_foreign" foreign key ("parent_id") references "parents" ("id") on delete cascade not deferrable');

    $blueprint = new Blueprint('users');
    $blueprint->foreign('parent_id')->references('id')->on('parents')->onDelete('cascade')->deferrable()->initiallyImmediate(false);
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add constraint "users_parent_id_foreign" foreign key ("parent_id") references "parents" ("id") on delete cascade deferrable initially deferred');

    $blueprint = new Blueprint('users');
    $blueprint->foreign('parent_id')->references('id')->on('parents')->onDelete('cascade')->deferrable()->notValid();
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "users" add constraint "users_parent_id_foreign" foreign key ("parent_id") references "parents" ("id") on delete cascade deferrable not valid');
});

test('adding geometry', function () {
    $blueprint = new Blueprint('geo');
    $blueprint->geometry('coordinates');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "geo" add column "coordinates" geography(geometry, 4326) not null');
});

test('adding point', function () {
    $blueprint = new Blueprint('geo');
    $blueprint->point('coordinates');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "geo" add column "coordinates" geography(point, 4326) not null');
});

test('adding line string', function () {
    $blueprint = new Blueprint('geo');
    $blueprint->linestring('coordinates');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "geo" add column "coordinates" geography(linestring, 4326) not null');
});

test('adding polygon', function () {
    $blueprint = new Blueprint('geo');
    $blueprint->polygon('coordinates');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "geo" add column "coordinates" geography(polygon, 4326) not null');
});

test('adding geometry collection', function () {
    $blueprint = new Blueprint('geo');
    $blueprint->geometrycollection('coordinates');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "geo" add column "coordinates" geography(geometrycollection, 4326) not null');
});

test('adding multi point', function () {
    $blueprint = new Blueprint('geo');
    $blueprint->multipoint('coordinates');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "geo" add column "coordinates" geography(multipoint, 4326) not null');
});

test('adding multi line string', function () {
    $blueprint = new Blueprint('geo');
    $blueprint->multilinestring('coordinates');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "geo" add column "coordinates" geography(multilinestring, 4326) not null');
});

test('adding multi polygon', function () {
    $blueprint = new Blueprint('geo');
    $blueprint->multipolygon('coordinates');
    $statements = $blueprint->toSql(getConnection(), getGrammar());

    expect($statements)->toHaveCount(1);
    expect($statements[0])->toBe('alter table "geo" add column "coordinates" geography(multipolygon, 4326) not null');
});

test('create database', function () {
    $connection = getConnection();
    $connection->shouldReceive('getConfig')->once()->once()->with('charset')->andReturn('utf8_foo');
    $statement = getGrammar()->compileCreateDatabase('my_database_a', $connection);

    $this->assertSame(
        'create database "my_database_a" encoding "utf8_foo"',
        $statement
    );

    $connection = getConnection();
    $connection->shouldReceive('getConfig')->once()->once()->with('charset')->andReturn('utf8_bar');
    $statement = getGrammar()->compileCreateDatabase('my_database_b', $connection);

    $this->assertSame(
        'create database "my_database_b" encoding "utf8_bar"',
        $statement
    );
});

test('drop database if exists', function () {
    $statement = getGrammar()->compileDropDatabaseIfExists('my_database_a');

    $this->assertSame(
        'drop database if exists "my_database_a"',
        $statement
    );

    $statement = getGrammar()->compileDropDatabaseIfExists('my_database_b');

    $this->assertSame(
        'drop database if exists "my_database_b"',
        $statement
    );
});

test('drop all tables escapes table names', function () {
    $statement = getGrammar()->compileDropAllTables(['alpha', 'beta', 'gamma']);

    expect($statement)->toBe('drop table "alpha","beta","gamma" cascade');
});

test('drop all views escapes table names', function () {
    $statement = getGrammar()->compileDropAllViews(['alpha', 'beta', 'gamma']);

    expect($statement)->toBe('drop view "alpha","beta","gamma" cascade');
});

test('drop all types escapes table names', function () {
    $statement = getGrammar()->compileDropAllTypes(['alpha', 'beta', 'gamma']);

    expect($statement)->toBe('drop type "alpha","beta","gamma" cascade');
});

test('grammars are macroable', function () {
    // compileReplace macro.
    getGrammar()::macro('compileReplace', function () {
        return true;
    });

    $c = getGrammar()::compileReplace();

    expect($c)->toBeTrue();
});

// Helpers
function getConnection()
{
    return m::mock(Connection::class);
}

function getGrammar()
{
    return new CockroachGrammar();
}

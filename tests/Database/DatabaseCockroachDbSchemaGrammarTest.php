<?php

namespace YlsIdeas\CockroachDb\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ForeignIdColumnDefinition;
use Illuminate\Support\Fluent;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use YlsIdeas\CockroachDb\Exceptions\FeatureNotSupportedException;
use YlsIdeas\CockroachDb\Schema\CockroachGrammar;

class DatabaseCockroachDbSchemaGrammarTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_basic_create_table()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('email');
        $blueprint->string('name')->collation('nb_NO.utf8');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('create table "users" ("id" serial not null primary key, "email" varchar(255) not null, "name" varchar(255) collate "nb_NO.utf8" not null)', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->increments('id');
        $blueprint->string('email');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "id" serial not null primary key, add column "email" varchar(255) not null', $statements[0]);
    }

    public function test_create_table_with_auto_increment_starting_value()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->increments('id')->startingValue(1000);
        $blueprint->string('email');
        $blueprint->string('name')->collation('nb_NO.utf8');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(2, $statements);
        $this->assertSame('create table "users" ("id" serial not null primary key, "email" varchar(255) not null, "name" varchar(255) collate "nb_NO.utf8" not null)', $statements[0]);
        $this->assertSame('alter sequence users_id_seq restart with 1000', $statements[1]);
    }

    public function test_create_table_and_comment_column()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('email')->comment('my first comment');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(2, $statements);
        $this->assertSame('create table "users" ("id" serial not null primary key, "email" varchar(255) not null)', $statements[0]);
        $this->assertSame('comment on column "users"."email" is \'my first comment\'', $statements[1]);
    }

    public function test_create_temporary_table()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->temporary();
        $blueprint->increments('id');
        $blueprint->string('email');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('create temporary table "users" ("id" serial not null primary key, "email" varchar(255) not null)', $statements[0]);
    }

    public function test_drop_table()
    {
        $blueprint = new Blueprint('users');
        $blueprint->drop();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('drop table "users"', $statements[0]);
    }

    public function test_drop_table_if_exists()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropIfExists();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('drop table if exists "users"', $statements[0]);
    }

    public function test_drop_column()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropColumn('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" drop column "foo"', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->dropColumn(['foo', 'bar']);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" drop column "foo", drop column "bar"', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->dropColumn('foo', 'bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" drop column "foo", drop column "bar"', $statements[0]);
    }

    public function test_drop_primary()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropPrimary();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" drop constraint "users_pkey"', $statements[0]);
    }

    public function test_drop_unique()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropUnique('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('drop index "users"@"foo" cascade', $statements[0]);
    }

    public function test_drop_index()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropIndex('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('drop index "foo"', $statements[0]);
    }

    public function test_drop_spatial_index()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->dropSpatialIndex(['coordinates']);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('drop index "geo_coordinates_spatialindex"', $statements[0]);
    }

    public function test_drop_foreign()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropForeign('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" drop constraint "foo"', $statements[0]);
    }

    public function test_drop_timestamps()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropTimestamps();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" drop column "created_at", drop column "updated_at"', $statements[0]);
    }

    public function test_drop_timestamps_tz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropTimestampsTz();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" drop column "created_at", drop column "updated_at"', $statements[0]);
    }

    public function test_drop_morphs()
    {
        $blueprint = new Blueprint('photos');
        $blueprint->dropMorphs('imageable');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(2, $statements);
        $this->assertSame('drop index "photos_imageable_type_imageable_id_index"', $statements[0]);
        $this->assertSame('alter table "photos" drop column "imageable_type", drop column "imageable_id"', $statements[1]);
    }

    public function test_rename_table()
    {
        $blueprint = new Blueprint('users');
        $blueprint->rename('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" rename to "foo"', $statements[0]);
    }

    public function test_rename_index()
    {
        $blueprint = new Blueprint('users');
        $blueprint->renameIndex('foo', 'bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter index "foo" rename to "bar"', $statements[0]);
    }

    public function test_adding_primary_key()
    {
        $blueprint = new Blueprint('users');
        $blueprint->primary('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add primary key ("foo")', $statements[0]);
    }

    public function test_adding_unique_key()
    {
        $blueprint = new Blueprint('users');
        $blueprint->unique('foo', 'bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add constraint "bar" unique ("foo")', $statements[0]);
    }

    public function test_adding_index()
    {
        $blueprint = new Blueprint('users');
        $blueprint->index(['foo', 'bar'], 'baz');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('create index "baz" on "users" ("foo", "bar")', $statements[0]);
    }

    public function test_adding_index_with_algorithm()
    {
        $blueprint = new Blueprint('users');
        $blueprint->index(['foo', 'bar'], 'baz', 'hash');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('create index "baz" on "users" using hash ("foo", "bar")', $statements[0]);
    }

    public function test_adding_spatial_index()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->spatialIndex('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('create index "geo_coordinates_spatialindex" on "geo" using gist ("coordinates")', $statements[0]);
    }

    public function test_adding_fluent_spatial_index()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->point('coordinates')->spatialIndex();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(2, $statements);
        $this->assertSame('create index "geo_coordinates_spatialindex" on "geo" using gist ("coordinates")', $statements[1]);
    }

    public function test_adding_raw_index()
    {
        $blueprint = new Blueprint('users');
        $blueprint->rawIndex('(function(column))', 'raw_index');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('create index "raw_index" on "users" ((function(column)))', $statements[0]);
    }

    public function test_adding_incrementing_id()
    {
        $blueprint = new Blueprint('users');
        $blueprint->increments('id');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "id" serial not null primary key', $statements[0]);
    }

    public function test_adding_small_incrementing_id()
    {
        $blueprint = new Blueprint('users');
        $blueprint->smallIncrements('id');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "id" smallserial not null primary key', $statements[0]);
    }

    public function test_adding_medium_incrementing_id()
    {
        $blueprint = new Blueprint('users');
        $blueprint->mediumIncrements('id');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "id" serial not null primary key', $statements[0]);
    }

    public function test_adding_id()
    {
        $blueprint = new Blueprint('users');
        $blueprint->id();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "id" bigserial not null primary key', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->id('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" bigserial not null primary key', $statements[0]);
    }

    public function test_adding_foreign_id()
    {
        $blueprint = new Blueprint('users');
        $foreignId = $blueprint->foreignId('foo');
        $blueprint->foreignId('company_id')->constrained();
        $blueprint->foreignId('laravel_idea_id')->constrained();
        $blueprint->foreignId('team_id')->references('id')->on('teams');
        $blueprint->foreignId('team_column_id')->constrained('teams');

        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertInstanceOf(ForeignIdColumnDefinition::class, $foreignId);
        $this->assertSame([
            'alter table "users" add column "foo" bigint not null, add column "company_id" bigint not null, add column "laravel_idea_id" bigint not null, add column "team_id" bigint not null, add column "team_column_id" bigint not null',
            'alter table "users" add constraint "users_company_id_foreign" foreign key ("company_id") references "companies" ("id")',
            'alter table "users" add constraint "users_laravel_idea_id_foreign" foreign key ("laravel_idea_id") references "laravel_ideas" ("id")',
            'alter table "users" add constraint "users_team_id_foreign" foreign key ("team_id") references "teams" ("id")',
            'alter table "users" add constraint "users_team_column_id_foreign" foreign key ("team_column_id") references "teams" ("id")',
        ], $statements);
    }

    public function test_adding_big_incrementing_id()
    {
        $blueprint = new Blueprint('users');
        $blueprint->bigIncrements('id');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "id" bigserial not null primary key', $statements[0]);
    }

    public function test_adding_string()
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" varchar(255) not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->string('foo', 100);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" varchar(100) not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->string('foo', 100)->nullable()->default('bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" varchar(100) null default \'bar\'', $statements[0]);
    }

    public function test_adding_text()
    {
        $blueprint = new Blueprint('users');
        $blueprint->text('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" text not null', $statements[0]);
    }

    public function test_adding_big_integer()
    {
        $blueprint = new Blueprint('users');
        $blueprint->bigInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" bigint not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->bigInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" bigserial not null primary key', $statements[0]);
    }

    public function test_adding_integer()
    {
        $blueprint = new Blueprint('users');
        $blueprint->integer('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" integer not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->integer('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" serial not null primary key', $statements[0]);
    }

    public function test_adding_medium_integer()
    {
        $blueprint = new Blueprint('users');
        $blueprint->mediumInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" integer not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->mediumInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" serial not null primary key', $statements[0]);
    }

    public function test_adding_tiny_integer()
    {
        $blueprint = new Blueprint('users');
        $blueprint->tinyInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" smallint not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->tinyInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" smallserial not null primary key', $statements[0]);
    }

    public function test_adding_small_integer()
    {
        $blueprint = new Blueprint('users');
        $blueprint->smallInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" smallint not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->smallInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" smallserial not null primary key', $statements[0]);
    }

    public function test_adding_float()
    {
        $blueprint = new Blueprint('users');
        $blueprint->float('foo', 5, 2);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" double precision not null', $statements[0]);
    }

    public function test_adding_double()
    {
        $blueprint = new Blueprint('users');
        $blueprint->double('foo', 15, 8);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" double precision not null', $statements[0]);
    }

    public function test_adding_decimal()
    {
        $blueprint = new Blueprint('users');
        $blueprint->decimal('foo', 5, 2);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" decimal(5, 2) not null', $statements[0]);
    }

    public function test_adding_boolean()
    {
        $blueprint = new Blueprint('users');
        $blueprint->boolean('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" boolean not null', $statements[0]);
    }

    public function test_adding_enum()
    {
        $blueprint = new Blueprint('users');
        $blueprint->enum('role', ['member', 'admin']);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "role" varchar(255) check ("role" in (\'member\', \'admin\')) not null', $statements[0]);
    }

    public function test_adding_date()
    {
        $blueprint = new Blueprint('users');
        $blueprint->date('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" date not null', $statements[0]);
    }

    public function test_adding_year()
    {
        $blueprint = new Blueprint('users');
        $blueprint->year('birth_year');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "birth_year" integer not null', $statements[0]);
    }

    public function test_adding_json()
    {
        $blueprint = new Blueprint('users');
        $blueprint->json('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" json not null', $statements[0]);
    }

    public function test_adding_jsonb()
    {
        $blueprint = new Blueprint('users');
        $blueprint->jsonb('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" jsonb not null', $statements[0]);
    }

    public function test_adding_date_time()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTime('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" timestamp(0) without time zone not null', $statements[0]);
    }

    public function test_adding_date_time_with_precision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTime('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" timestamp(1) without time zone not null', $statements[0]);
    }

    public function test_adding_date_time_with_null_precision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTime('created_at', null);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" timestamp without time zone not null', $statements[0]);
    }

    public function test_adding_date_time_tz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTimeTz('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" timestamp(0) with time zone not null', $statements[0]);
    }

    public function test_adding_date_time_tz_with_precision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTimeTz('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" timestamp(1) with time zone not null', $statements[0]);
    }

    public function test_adding_date_time_tz_with_null_precision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTimeTz('created_at', null);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" timestamp with time zone not null', $statements[0]);
    }

    public function test_adding_time()
    {
        $blueprint = new Blueprint('users');
        $blueprint->time('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" time(0) without time zone not null', $statements[0]);
    }

    public function test_adding_time_with_precision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->time('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" time(1) without time zone not null', $statements[0]);
    }

    public function test_adding_time_with_null_precision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->time('created_at', null);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" time without time zone not null', $statements[0]);
    }

    public function test_adding_time_tz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timeTz('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" time(0) with time zone not null', $statements[0]);
    }

    public function test_adding_time_tz_with_precision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timeTz('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" time(1) with time zone not null', $statements[0]);
    }

    public function test_adding_time_tz_with_null_precision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timeTz('created_at', null);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" time with time zone not null', $statements[0]);
    }

    public function test_adding_timestamp()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamp('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" timestamp(0) without time zone not null', $statements[0]);
    }

    public function test_adding_timestamp_with_precision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamp('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" timestamp(1) without time zone not null', $statements[0]);
    }

    public function test_adding_timestamp_with_null_precision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamp('created_at', null);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" timestamp without time zone not null', $statements[0]);
    }

    public function test_adding_timestamp_tz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestampTz('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" timestamp(0) with time zone not null', $statements[0]);
    }

    public function test_adding_timestamp_tz_with_precision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestampTz('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" timestamp(1) with time zone not null', $statements[0]);
    }

    public function test_adding_timestamp_tz_with_null_precision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestampTz('created_at', null);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" timestamp with time zone not null', $statements[0]);
    }

    public function test_adding_timestamps()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamps();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" timestamp(0) without time zone null, add column "updated_at" timestamp(0) without time zone null', $statements[0]);
    }

    public function test_adding_timestamps_tz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestampsTz();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" timestamp(0) with time zone null, add column "updated_at" timestamp(0) with time zone null', $statements[0]);
    }

    public function test_adding_binary()
    {
        $blueprint = new Blueprint('users');
        $blueprint->binary('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" bytea not null', $statements[0]);
    }

    public function test_adding_uuid()
    {
        $blueprint = new Blueprint('users');
        $blueprint->uuid('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" uuid not null', $statements[0]);
    }

    public function test_adding_foreign_uuid()
    {
        $blueprint = new Blueprint('users');
        $foreignUuid = $blueprint->foreignUuid('foo');
        $blueprint->foreignUuid('company_id')->constrained();
        $blueprint->foreignUuid('laravel_idea_id')->constrained();
        $blueprint->foreignUuid('team_id')->references('id')->on('teams');
        $blueprint->foreignUuid('team_column_id')->constrained('teams');

        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertInstanceOf(ForeignIdColumnDefinition::class, $foreignUuid);
        $this->assertSame([
            'alter table "users" add column "foo" uuid not null, add column "company_id" uuid not null, add column "laravel_idea_id" uuid not null, add column "team_id" uuid not null, add column "team_column_id" uuid not null',
            'alter table "users" add constraint "users_company_id_foreign" foreign key ("company_id") references "companies" ("id")',
            'alter table "users" add constraint "users_laravel_idea_id_foreign" foreign key ("laravel_idea_id") references "laravel_ideas" ("id")',
            'alter table "users" add constraint "users_team_id_foreign" foreign key ("team_id") references "teams" ("id")',
            'alter table "users" add constraint "users_team_column_id_foreign" foreign key ("team_column_id") references "teams" ("id")',
        ], $statements);
    }

//    public function test_adding_generated_as()
//    {
//        $blueprint = new Blueprint('users');
//        $blueprint->increments('foo')->generatedAs();
//        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
//        $this->assertCount(1, $statements);
//        $this->assertSame('alter table "users" add column "foo" integer not null generated by default as identity primary key', $statements[0]);
//        // With always modifier
//        $blueprint = new Blueprint('users');
//        $blueprint->increments('foo')->generatedAs()->always();
//        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
//        $this->assertCount(1, $statements);
//        $this->assertSame('alter table "users" add column "foo" integer not null generated always as identity primary key', $statements[0]);
//        // With sequence options
//        $blueprint = new Blueprint('users');
//        $blueprint->increments('foo')->generatedAs('increment by 10 start with 100');
//        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
//        $this->assertCount(1, $statements);
//        $this->assertSame('alter table "users" add column "foo" integer not null generated by default as identity (increment by 10 start with 100) primary key', $statements[0]);
//        // Not a primary key
//        $blueprint = new Blueprint('users');
//        $blueprint->integer('foo')->generatedAs();
//        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
//        $this->assertCount(1, $statements);
//        $this->assertSame('alter table "users" add column "foo" integer not null generated by default as identity', $statements[0]);
//    }

    /**
     * @dataProvider generatedAsStatements
     */
    public function test_adding_generated_as(callable $alter, string $expected)
    {
        $blueprint = new Blueprint('users');
        $alter($blueprint);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame($expected, $statements[0]);
    }

    public function generatedAsStatements(): \Generator
    {
        yield 'default' => [
            fn (Blueprint $blueprint) => $blueprint->increments('foo')->generatedAs(),
            'alter table "users" add column "foo" integer not null generated by default as identity primary key',
        ];

        yield 'With always modifier' => [
            fn (Blueprint $blueprint) => $blueprint->increments('foo')->generatedAs()->always(),
            'alter table "users" add column "foo" integer not null generated always as identity primary key',
        ];

        yield 'With sequence options' => [
            fn (Blueprint $blueprint) => $blueprint->increments('foo')->generatedAs('increment by 10 start with 100'),
            'alter table "users" add column "foo" integer not null generated by default as identity (increment by 10 start with 100) primary key',
        ];

        yield 'Not a primary key' => [
            fn (Blueprint $blueprint) => $blueprint->integer('foo')->generatedAs(),
            'alter table "users" add column "foo" integer not null generated by default as identity',
        ];
    }

    public function test_adding_virtual_as()
    {
        $blueprint = new Blueprint('users');
        $blueprint->integer('foo')->nullable();
        $blueprint->boolean('bar')->virtualAs('foo is not null');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" integer null, add column "bar" boolean not null generated always as (foo is not null)', $statements[0]);
    }

    public function test_adding_stored_as()
    {
        $blueprint = new Blueprint('users');
        $blueprint->integer('foo')->nullable();
        $blueprint->boolean('bar')->storedAs('foo is not null');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" integer null, add column "bar" boolean not null generated always as (foo is not null) stored', $statements[0]);
    }

    public function test_adding_ip_address()
    {
        $blueprint = new Blueprint('users');
        $blueprint->ipAddress('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" inet not null', $statements[0]);
    }

    public function test_adding_mac_address()
    {
        $blueprint = new Blueprint('users');
        $blueprint->macAddress('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" macaddr not null', $statements[0]);
    }

    public function test_compile_foreign()
    {
        $blueprint = new Blueprint('users');
        $blueprint->foreign('parent_id')->references('id')->on('parents')->onDelete('cascade')->deferrable();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add constraint "users_parent_id_foreign" foreign key ("parent_id") references "parents" ("id") on delete cascade deferrable', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->foreign('parent_id')->references('id')->on('parents')->onDelete('cascade')->deferrable(false)->initiallyImmediate();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add constraint "users_parent_id_foreign" foreign key ("parent_id") references "parents" ("id") on delete cascade not deferrable', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->foreign('parent_id')->references('id')->on('parents')->onDelete('cascade')->deferrable()->initiallyImmediate(false);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add constraint "users_parent_id_foreign" foreign key ("parent_id") references "parents" ("id") on delete cascade deferrable initially deferred', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->foreign('parent_id')->references('id')->on('parents')->onDelete('cascade')->deferrable()->notValid();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add constraint "users_parent_id_foreign" foreign key ("parent_id") references "parents" ("id") on delete cascade deferrable not valid', $statements[0]);
    }

    public function test_adding_geometry()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->geometry('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" geography(geometry, 4326) not null', $statements[0]);
    }

    public function test_adding_point()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->point('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" geography(point, 4326) not null', $statements[0]);
    }

    public function test_adding_line_string()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->linestring('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" geography(linestring, 4326) not null', $statements[0]);
    }

    public function test_adding_polygon()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->polygon('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" geography(polygon, 4326) not null', $statements[0]);
    }

    public function test_adding_geometry_collection()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->geometrycollection('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" geography(geometrycollection, 4326) not null', $statements[0]);
    }

    public function test_adding_multi_point()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->multipoint('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" geography(multipoint, 4326) not null', $statements[0]);
    }

    public function test_adding_multi_line_string()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->multilinestring('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" geography(multilinestring, 4326) not null', $statements[0]);
    }

    public function test_adding_multi_polygon()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->multipolygon('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" geography(multipolygon, 4326) not null', $statements[0]);
    }

    public function test_create_database()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->once()->once()->with('charset')->andReturn('utf8_foo');
        $statement = $this->getGrammar()->compileCreateDatabase('my_database_a', $connection);

        $this->assertSame(
            'create database "my_database_a" encoding "utf8_foo"',
            $statement
        );

        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->once()->once()->with('charset')->andReturn('utf8_bar');
        $statement = $this->getGrammar()->compileCreateDatabase('my_database_b', $connection);

        $this->assertSame(
            'create database "my_database_b" encoding "utf8_bar"',
            $statement
        );
    }

    public function test_drop_database_if_exists()
    {
        $statement = $this->getGrammar()->compileDropDatabaseIfExists('my_database_a');

        $this->assertSame(
            'drop database if exists "my_database_a"',
            $statement
        );

        $statement = $this->getGrammar()->compileDropDatabaseIfExists('my_database_b');

        $this->assertSame(
            'drop database if exists "my_database_b"',
            $statement
        );
    }

    public function test_drop_all_tables_escapes_table_names()
    {
        $statement = $this->getGrammar()->compileDropAllTables(['alpha', 'beta', 'gamma']);

        $this->assertSame('drop table "alpha","beta","gamma" cascade', $statement);
    }

    public function test_drop_all_views_escapes_table_names()
    {
        $statement = $this->getGrammar()->compileDropAllViews(['alpha', 'beta', 'gamma']);

        $this->assertSame('drop view "alpha","beta","gamma" cascade', $statement);
    }

    public function test_drop_all_types_escapes_table_names()
    {
        $statement = $this->getGrammar()->compileDropAllTypes(['alpha', 'beta', 'gamma']);

        $this->assertSame('drop type "alpha","beta","gamma" cascade', $statement);
    }

    public function test_creating_fulltext_indexes_throws_an_exception()
    {
        $this->expectException(FeatureNotSupportedException::class);
        $blueprint = new Blueprint('fulltext');
        $fluent = new Fluent();
        $this->getGrammar()->compileFulltext($blueprint, $fluent);
    }

    protected function getConnection()
    {
        return m::mock(Connection::class);
    }

    public function getGrammar()
    {
        return new CockroachGrammar();
    }

    public function test_grammars_are_macroable()
    {
        // compileReplace macro.
        $this->getGrammar()::macro('compileReplace', function () {
            return true;
        });

        $c = $this->getGrammar()::compileReplace();

        $this->assertTrue($c);
    }
}

<?php

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

test('sole', function () {
    $expected = ['id' => '1', 'title' => 'Foo Post'];

    expect((array) DB::table('posts')->where('title', 'Foo Post')->select('id', 'title')->sole())->toEqual($expected);
});

test('sole fails for multiple records', function () {
    DB::table('posts')->insert([
        ['title' => 'Foo Post', 'content' => 'Lorem Ipsum.', 'created_at' => new Carbon('2017-11-12 13:14:15')],
    ]);

    $this->expectException(MultipleRecordsFoundException::class);

    DB::table('posts')->where('title', 'Foo Post')->sole();
});

test('sole fails if no records', function () {
    $this->expectException(RecordsNotFoundException::class);

    DB::table('posts')->where('title', 'Baz Post')->sole();
});

test('select', function () {
    $expected = ['id' => '1', 'title' => 'Foo Post'];

    expect((array) DB::table('posts')->select('id', 'title')->first())->toEqual($expected);
    expect((array) DB::table('posts')->select(['id', 'title'])->first())->toEqual($expected);
});

test('select replaces existing selects', function () {
    $this->assertEquals(
        ['id' => '1', 'title' => 'Foo Post'],
        (array) DB::table('posts')->select('content')->select(['id', 'title'])->first()
    );
});

test('select with sub query', function () {
    $this->assertEquals(
        ['id' => '1', 'title' => 'Foo Post', 'foo' => 'Lorem Ipsum.'],
        (array) DB::table('posts')->select(['id', 'title', 'foo' => function ($query) {
            $query->select('content');
        }])->first()
    );
});

test('add select', function () {
    $expected = ['id' => '1', 'title' => 'Foo Post', 'content' => 'Lorem Ipsum.'];

    expect((array) DB::table('posts')->select('id')->addSelect('title', 'content')->first())->toEqual($expected);
    expect((array) DB::table('posts')->select('id')->addSelect(['title', 'content'])->first())->toEqual($expected);
    expect((array) DB::table('posts')->addSelect(['id', 'title', 'content'])->first())->toEqual($expected);
});

test('add select with sub query', function () {
    $this->assertEquals(
        ['id' => '1', 'title' => 'Foo Post', 'foo' => 'Lorem Ipsum.'],
        (array) DB::table('posts')->addSelect(['id', 'title', 'foo' => function ($query) {
            $query->select('content');
        }])->first()
    );
});

test('from with alias', function () {
    expect(DB::table('posts', 'alias')->select('alias.*')->get())->toHaveCount(2);
});

test('from with sub query', function () {
    $this->assertSame(
        'Fake Post',
        DB::table(function ($query) {
            $query->selectRaw("'Fake Post' as title");
        }, 'posts')->first()->title
    );
});

test('where value sub query', function () {
    $subQuery = function ($query) {
        $query->selectRaw("'Sub query value'");
    };

    expect(DB::table('posts')->where($subQuery, 'Sub query value')->exists())->toBeTrue();
    expect(DB::table('posts')->where($subQuery, 'Does not match')->exists())->toBeFalse();
    expect(DB::table('posts')->where($subQuery, '!=', 'Does not match')->exists())->toBeTrue();
});

test('where value sub query builder', function () {
    $subQuery = DB::table('posts')->selectRaw("'Sub query value'")->limit(1);

    expect(DB::table('posts')->where($subQuery, 'Sub query value')->exists())->toBeTrue();
    expect(DB::table('posts')->where($subQuery, 'Does not match')->exists())->toBeFalse();
    expect(DB::table('posts')->where($subQuery, '!=', 'Does not match')->exists())->toBeTrue();
});

test('where date', function () {
    expect(DB::table('posts')->whereDate('created_at', '2018-01-02')->count())->toBe(1);
    expect(DB::table('posts')->whereDate('created_at', new Carbon('2018-01-02'))->count())->toBe(1);
});

test('or where date', function () {
    expect(DB::table('posts')->where('id', 1)->orWhereDate('created_at', '2018-01-02')->count())->toBe(2);
    expect(DB::table('posts')->where('id', 1)->orWhereDate('created_at', new Carbon('2018-01-02'))->count())->toBe(2);
});

test('where day', function () {
    expect(DB::table('posts')->whereDay('created_at', '02')->count())->toBe(1);
    expect(DB::table('posts')->whereDay('created_at', 2)->count())->toBe(1);
    expect(DB::table('posts')->whereDay('created_at', new Carbon('2018-01-02'))->count())->toBe(1);
});

test('or where day', function () {
    expect(DB::table('posts')->where('id', 1)->orWhereDay('created_at', '02')->count())->toBe(2);
    expect(DB::table('posts')->where('id', 1)->orWhereDay('created_at', 2)->count())->toBe(2);
    expect(DB::table('posts')->where('id', 1)->orWhereDay('created_at', new Carbon('2018-01-02'))->count())->toBe(2);
});

test('where month', function () {
    expect(DB::table('posts')->whereMonth('created_at', '01')->count())->toBe(1);
    expect(DB::table('posts')->whereMonth('created_at', 1)->count())->toBe(1);
    expect(DB::table('posts')->whereMonth('created_at', new Carbon('2018-01-02'))->count())->toBe(1);
});

test('or where month', function () {
    expect(DB::table('posts')->where('id', 1)->orWhereMonth('created_at', '01')->count())->toBe(2);
    expect(DB::table('posts')->where('id', 1)->orWhereMonth('created_at', 1)->count())->toBe(2);
    expect(DB::table('posts')->where('id', 1)->orWhereMonth('created_at', new Carbon('2018-01-02'))->count())->toBe(2);
});

test('where year', function () {
    expect(DB::table('posts')->whereYear('created_at', '2018')->count())->toBe(1);
    expect(DB::table('posts')->whereYear('created_at', 2018)->count())->toBe(1);
    expect(DB::table('posts')->whereYear('created_at', new Carbon('2018-01-02'))->count())->toBe(1);
});

test('or where year', function () {
    expect(DB::table('posts')->where('id', 1)->orWhereYear('created_at', '2018')->count())->toBe(2);
    expect(DB::table('posts')->where('id', 1)->orWhereYear('created_at', 2018)->count())->toBe(2);
    expect(DB::table('posts')->where('id', 1)->orWhereYear('created_at', new Carbon('2018-01-02'))->count())->toBe(2);
});

test('where time', function () {
    expect(DB::table('posts')->whereTime('created_at', '03:04:05')->count())->toBe(1);
    expect(DB::table('posts')->whereTime('created_at', new Carbon('2018-01-02 03:04:05'))->count())->toBe(1);
});

test('or where time', function () {
    expect(DB::table('posts')->where('id', 1)->orWhereTime('created_at', '03:04:05')->count())->toBe(2);
    expect(DB::table('posts')->where('id', 1)->orWhereTime('created_at', new Carbon('2018-01-02 03:04:05'))->count())->toBe(2);
});

test('paginate with specific columns', function () {
    $result = DB::table('posts')->paginate(5, ['title', 'content']);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    $this->assertEquals($result->items(), [
        (object) ['title' => 'Foo Post', 'content' => 'Lorem Ipsum.'],
        (object) ['title' => 'Bar Post', 'content' => 'Lorem Ipsum.'],
    ]);
});

test('chunk map', function () {
    DB::enableQueryLog();

    $results = DB::table('posts')->orderBy('id')->chunkMap(function ($post) {
        return $post->title;
    }, 1);

    expect($results)->toHaveCount(2);
    expect($results[0])->toBe('Foo Post');
    expect($results[1])->toBe('Bar Post');
    expect(DB::getQueryLog())->toHaveCount(3);
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('posts', function (Blueprint $table) {
        $table->increments('id');
        $table->string('title');
        $table->text('content');
        $table->timestamp('created_at');
    });

    DB::table('posts')->insert([
        ['id' => '1', 'title' => 'Foo Post', 'content' => 'Lorem Ipsum.', 'created_at' => new Carbon('2017-11-12 13:14:15')],
        ['id' => '2', 'title' => 'Bar Post', 'content' => 'Lorem Ipsum.', 'created_at' => new Carbon('2018-01-02 03:04:05')],
    ]);
}

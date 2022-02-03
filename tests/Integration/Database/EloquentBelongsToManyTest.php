<?php

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(DatabaseTestCase::class);

test('basic create and retrieve', function () {
    Carbon::setTestNow('2017-10-10 10:10:10');

    $post = Post::create(['id' => 1, 'title' => Str::random()]);

    $tag = Tag::create(['id' => 1, 'name' => Str::random()]);
    $tag2 = Tag::create(['id' => 2, 'name' => Str::random()]);
    $tag3 = Tag::create(['id' => 3, 'name' => Str::random()]);

    $post->tags()->sync([
        $tag->id => ['flag' => 'taylor'],
        $tag2->id => ['flag' => ''],
        $tag3->id => ['flag' => 'exclude'],
    ]);

    // Tags with flag = exclude should be excluded
    $this->assertCount(2, $post->tags);
    $this->assertInstanceOf(Collection::class, $post->tags);
    $this->assertEquals($tag->name, $post->tags[0]->name);
    $this->assertEquals($tag2->name, $post->tags[1]->name);

    // Testing on the pivot model
    $this->assertInstanceOf(Pivot::class, $post->tags[0]->pivot);
    $this->assertEquals($post->id, $post->tags[0]->pivot->post_id);
    $this->assertSame('post_id', $post->tags[0]->pivot->getForeignKey());
    $this->assertSame('tag_id', $post->tags[0]->pivot->getOtherKey());
    $this->assertSame('posts_tags', $post->tags[0]->pivot->getTable());
    $this->assertEquals(
        [
            'post_id' => '1', 'tag_id' => '1', 'flag' => 'taylor',
            'created_at' => '2017-10-10T10:10:10.000000Z', 'updated_at' => '2017-10-10T10:10:10.000000Z',
        ],
        $post->tags[0]->pivot->toArray()
    );
});

test('refresh on other model works', function () {
    $post = Post::create(['title' => Str::random()]);
    $tag = Tag::create(['name' => $tagName = Str::random()]);

    $post->tags()->sync([
        $tag->id,
    ]);

    $post->load('tags');

    $loadedTag = $post->tags()->first();

    $tag->update(['name' => 'newName']);

    $this->assertEquals($tagName, $loadedTag->name);

    $this->assertEquals($tagName, $post->tags[0]->name);

    $loadedTag->refresh();

    $this->assertSame('newName', $loadedTag->name);

    $post->refresh();

    $this->assertSame('newName', $post->tags[0]->name);
});

test('custom pivot class', function () {
    Carbon::setTestNow('2017-10-10 10:10:10');

    $post = Post::create(['id' => 1, 'title' => Str::random()]);

    $tag = TagWithCustomPivot::create(['id' => 1, 'name' => Str::random()]);

    $post->tagsWithCustomPivot()->attach($tag->id);

    $this->assertInstanceOf(PostTagPivot::class, $post->tagsWithCustomPivot[0]->pivot);
    $this->assertEquals('1507630210', $post->tagsWithCustomPivot[0]->pivot->created_at);

    $this->assertInstanceOf(PostTagPivot::class, $post->tagsWithCustomPivotClass[0]->pivot);
    $this->assertSame('posts_tags', $post->tagsWithCustomPivotClass()->getTable());

    $this->assertEquals([
        'post_id' => '1',
        'tag_id' => '1',
    ], $post->tagsWithCustomAccessor[0]->tag->toArray());

    $pivot = $post->tagsWithCustomPivot[0]->pivot;
    $pivot->tag_id = 2;
    $pivot->save();

    $this->assertEquals(1, PostTagPivot::count());
    $this->assertEquals(1, PostTagPivot::first()->post_id);
    $this->assertEquals(2, PostTagPivot::first()->tag_id);
});

test('custom pivot class using sync', function () {
    Carbon::setTestNow('2017-10-10 10:10:10');

    $post = Post::create(['title' => Str::random()]);

    $tag = TagWithCustomPivot::create(['name' => Str::random()]);

    $results = $post->tagsWithCustomPivot()->sync([
        $tag->id => ['flag' => 1],
    ]);

    $this->assertNotEmpty($results['attached']);

    $results = $post->tagsWithCustomPivot()->sync([
        $tag->id => ['flag' => 1],
    ]);

    $this->assertEmpty($results['updated']);

    $results = $post->tagsWithCustomPivot()->sync([]);

    $this->assertNotEmpty($results['detached']);
});

test('custom pivot class using update existing pivot', function () {
    Carbon::setTestNow('2017-10-10 10:10:10');

    $post = Post::create(['title' => Str::random()]);
    $tag = TagWithCustomPivot::create(['name' => Str::random()]);

    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => $tag->id, 'flag' => 'empty'],
    ]);

    // Test on actually existing pivot
    $this->assertEquals(
        1,
        $post->tagsWithCustomExtraPivot()->updateExistingPivot($tag->id, ['flag' => 'exclude'])
    );
    foreach ($post->tagsWithCustomExtraPivot as $tag) {
        $this->assertSame('exclude', $tag->pivot->flag);
    }

    // Test on non-existent pivot
    $this->assertEquals(
        0,
        $post->tagsWithCustomExtraPivot()->updateExistingPivot(0, ['flag' => 'exclude'])
    );
});

/**/
test('custom pivot class updates timestamps', function () {
    Carbon::setTestNow('2017-10-10 10:10:10');

    $post = Post::create(['title' => Str::random()]);
    $tag = TagWithCustomPivot::create(['name' => Str::random()]);

    DB::table('posts_tags')->insert([
        [
            'post_id' => $post->id, 'tag_id' => $tag->id, 'flag' => 'empty',
            'created_at' => '2017-10-10 10:10:10',
            'updated_at' => '2017-10-10 10:10:10',
        ],
    ]);

    Carbon::setTestNow('2017-10-10 10:10:20'); // +10 seconds

    $this->assertEquals(
        1,
        $post->tagsWithCustomExtraPivot()->updateExistingPivot($tag->id, ['flag' => 'exclude'])
    );
    foreach ($post->tagsWithCustomExtraPivot as $tag) {
        $this->assertSame('exclude', $tag->pivot->flag);
        $this->assertEquals('2017-10-10 10:10:10', $tag->pivot->getAttributes()['created_at']);
        $this->assertEquals('2017-10-10 10:10:20', $tag->pivot->getAttributes()['updated_at']); // +10 seconds
    }
})->group('SkipMSSQL');

test('attach method', function () {
    $post = Post::create(['title' => Str::random()]);

    $tag = Tag::create(['name' => Str::random()]);
    $tag2 = Tag::create(['name' => Str::random()]);
    $tag3 = Tag::create(['name' => Str::random()]);
    $tag4 = Tag::create(['name' => Str::random()]);
    $tag5 = Tag::create(['name' => Str::random()]);
    $tag6 = Tag::create(['name' => Str::random()]);
    $tag7 = Tag::create(['name' => Str::random()]);
    $tag8 = Tag::create(['name' => Str::random()]);

    $post->tags()->attach($tag->id);
    $this->assertEquals($tag->name, $post->tags[0]->name);
    $this->assertNotNull($post->tags[0]->pivot->created_at);

    $post->tags()->attach($tag2->id, ['flag' => 'taylor']);
    $post->load('tags');
    $this->assertEquals($tag2->name, $post->tags[1]->name);
    $this->assertSame('taylor', $post->tags[1]->pivot->flag);

    $post->tags()->attach([$tag3->id, $tag4->id]);
    $post->load('tags');
    $this->assertEquals($tag3->name, $post->tags[2]->name);
    $this->assertEquals($tag4->name, $post->tags[3]->name);

    $post->tags()->attach([$tag5->id => ['flag' => 'mohamed'], $tag6->id => ['flag' => 'adam']]);
    $post->load('tags');
    $this->assertEquals($tag5->name, $post->tags[4]->name);
    $this->assertSame('mohamed', $post->tags[4]->pivot->flag);
    $this->assertEquals($tag6->name, $post->tags[5]->name);
    $this->assertSame('adam', $post->tags[5]->pivot->flag);

    $post->tags()->attach(new Collection([$tag7, $tag8]));
    $post->load('tags');
    $this->assertEquals($tag7->name, $post->tags[6]->name);
    $this->assertEquals($tag8->name, $post->tags[7]->name);
});

test('detach method', function () {
    $post = Post::create(['title' => Str::random()]);

    $tag = Tag::create(['name' => Str::random()]);
    $tag2 = Tag::create(['name' => Str::random()]);
    $tag3 = Tag::create(['name' => Str::random()]);
    $tag4 = Tag::create(['name' => Str::random()]);
    $tag5 = Tag::create(['name' => Str::random()]);
    Tag::create(['name' => Str::random()]);
    Tag::create(['name' => Str::random()]);

    $post->tags()->attach(Tag::all());

    $this->assertEquals(Tag::pluck('name'), $post->tags->pluck('name'));

    $post->tags()->detach($tag->id);
    $post->load('tags');
    $this->assertEquals(
        Tag::whereNotIn('id', [$tag->id])->pluck('name'),
        $post->tags->pluck('name')
    );

    $post->tags()->detach([$tag2->id, $tag3->id]);
    $post->load('tags');
    $this->assertEquals(
        Tag::whereNotIn('id', [$tag->id, $tag2->id, $tag3->id])->pluck('name'),
        $post->tags->pluck('name')
    );

    $post->tags()->detach(new Collection([$tag4, $tag5]));
    $post->load('tags');
    $this->assertEquals(
        Tag::whereNotIn('id', [$tag->id, $tag2->id, $tag3->id, $tag4->id, $tag5->id])->pluck('name'),
        $post->tags->pluck('name')
    );

    $this->assertCount(2, $post->tags);
    $post->tags()->detach();
    $post->load('tags');
    $this->assertCount(0, $post->tags);
});

test('first method', function () {
    $post = Post::create(['title' => Str::random()]);

    $tag = Tag::create(['name' => Str::random()]);

    $post->tags()->attach(Tag::all());

    $this->assertEquals($tag->name, $post->tags()->first()->name);
});

test('first or fail method', function () {
    $this->expectException(ModelNotFoundException::class);

    $post = Post::create(['title' => Str::random()]);

    $post->tags()->firstOrFail(['id']);
});

test('find method', function () {
    $post = Post::create(['title' => Str::random()]);

    $tag = Tag::create(['name' => Str::random()]);
    $tag2 = Tag::create(['name' => Str::random()]);

    $post->tags()->attach(Tag::all());

    $this->assertEquals($tag2->name, $post->tags()->find($tag2->id)->name);
    $this->assertCount(0, $post->tags()->findMany([]));
    $this->assertCount(2, $post->tags()->findMany([$tag->id, $tag2->id]));
    $this->assertCount(0, $post->tags()->findMany(new Collection()));
    $this->assertCount(2, $post->tags()->findMany(new Collection([$tag->id, $tag2->id])));
});

test('find or fail method', function () {
    $this->expectException(ModelNotFoundException::class);
    $this->expectExceptionMessage('No query results for model [YlsIdeas\CockroachDb\Tests\Integration\Database\Tag] 10');

    $post = Post::create(['title' => Str::random()]);

    Tag::create(['id' => 1, 'name' => Str::random()]);

    $post->tags()->attach(Tag::all());

    $post->tags()->findOrFail(10);
});

test('find or fail method with many', function () {
    $this->expectException(ModelNotFoundException::class);
    $this->expectExceptionMessage('No query results for model [YlsIdeas\CockroachDb\Tests\Integration\Database\Tag] 10, 11');

    $post = Post::create(['title' => Str::random()]);

    Tag::create(['name' => Str::random()]);

    $post->tags()->attach(Tag::all());

    $post->tags()->findOrFail([10, 11]);
});

test('find or fail method with many using collection', function () {
    $this->expectException(ModelNotFoundException::class);
    $this->expectExceptionMessage('No query results for model [YlsIdeas\CockroachDb\Tests\Integration\Database\Tag] 10, 11');

    $post = Post::create(['title' => Str::random()]);

    Tag::create(['name' => Str::random()]);

    $post->tags()->attach(Tag::all());

    $post->tags()->findOrFail(new Collection([10, 11]));
});

test('find or new method', function () {
    $post = Post::create(['title' => Str::random()]);

    $tag = Tag::create(['name' => Str::random()]);

    $post->tags()->attach(Tag::all());

    $this->assertEquals($tag->id, $post->tags()->findOrNew($tag->id)->id);

    $this->assertNull($post->tags()->findOrNew(666)->id);
    $this->assertInstanceOf(Tag::class, $post->tags()->findOrNew(666));
});

test('first or new method', function () {
    $post = Post::create(['title' => Str::random()]);

    $tag = Tag::create(['name' => Str::random()]);

    $post->tags()->attach(Tag::all());

    $this->assertEquals($tag->id, $post->tags()->firstOrNew(['id' => $tag->id])->id);

    $this->assertFalse($post->tags()->firstOrNew(['id' => 666])->exists);
    $this->assertInstanceOf(Tag::class, $post->tags()->firstOrNew(['id' => 666]));
});

test('first or create method', function () {
    $post = Post::create(['title' => Str::random()]);

    $tag = Tag::create(['name' => Str::random()]);

    $post->tags()->attach(Tag::all());

    $this->assertEquals($tag->id, $post->tags()->firstOrCreate(['name' => $tag->name])->id);

    $new = $post->tags()->firstOrCreate(['name' => 'wavez']);
    $this->assertSame('wavez', $new->name);
    $this->assertNotNull($new->id);
});

test('update or create method', function () {
    $post = Post::create(['title' => Str::random()]);

    $tag = Tag::create(['name' => Str::random()]);

    $post->tags()->attach(Tag::all());

    $post->tags()->updateOrCreate(['id' => $tag->id], ['name' => 'wavez']);
    $this->assertSame('wavez', $tag->fresh()->name);

    $post->tags()->updateOrCreate(['id' => 666], ['name' => 'dives']);
    $this->assertNotNull($post->tags()->whereName('dives')->first());
});

test('sync method', function () {
    $post = Post::create(['title' => Str::random()]);

    $tag = Tag::create(['name' => Str::random()]);
    $tag2 = Tag::create(['name' => Str::random()]);
    $tag3 = Tag::create(['name' => Str::random()]);
    $tag4 = Tag::create(['name' => Str::random()]);

    $post->tags()->sync([$tag->id, $tag2->id]);

    $this->assertEquals(
        Tag::whereIn('id', [$tag->id, $tag2->id])->pluck('name'),
        $post->load('tags')->tags->pluck('name')
    );

    $output = $post->tags()->sync([$tag->id, $tag3->id, $tag4->id]);

    $this->assertEquals(
        Tag::whereIn('id', [$tag->id, $tag3->id, $tag4->id])->pluck('name'),
        $post->load('tags')->tags->pluck('name')
    );

    $this->assertEquals([
        'attached' => [$tag3->id, $tag4->id],
        'detached' => [1 => $tag2->id],
        'updated' => [],
    ], $output);

    $post->tags()->sync([]);
    $this->assertEmpty($post->load('tags')->tags);

    $post->tags()->sync([
        $tag->id => ['flag' => 'taylor'],
        $tag2->id => ['flag' => 'mohamed'],
    ]);
    $post->load('tags');
    $this->assertEquals($tag->name, $post->tags[0]->name);
    $this->assertSame('taylor', $post->tags[0]->pivot->flag);
    $this->assertEquals($tag2->name, $post->tags[1]->name);
    $this->assertSame('mohamed', $post->tags[1]->pivot->flag);
});

test('sync without detaching method', function () {
    $post = Post::create(['title' => Str::random()]);

    $tag = Tag::create(['name' => Str::random()]);
    $tag2 = Tag::create(['name' => Str::random()]);

    $post->tags()->sync([$tag->id]);

    $this->assertEquals(
        Tag::whereIn('id', [$tag->id])->pluck('name'),
        $post->load('tags')->tags->pluck('name')
    );

    $post->tags()->syncWithoutDetaching([$tag2->id]);

    $this->assertEquals(
        Tag::whereIn('id', [$tag->id, $tag2->id])->pluck('name'),
        $post->load('tags')->tags->pluck('name')
    );
});

test('toggle method', function () {
    $post = Post::create(['title' => Str::random()]);

    $tag = Tag::create(['name' => Str::random()]);
    $tag2 = Tag::create(['name' => Str::random()]);

    $post->tags()->toggle([$tag->id]);

    $this->assertEquals(
        Tag::whereIn('id', [$tag->id])->pluck('name'),
        $post->load('tags')->tags->pluck('name')
    );

    $post->tags()->toggle([$tag2->id, $tag->id]);

    $this->assertEquals(
        Tag::whereIn('id', [$tag2->id])->pluck('name'),
        $post->load('tags')->tags->pluck('name')
    );

    $post->tags()->toggle([$tag2->id, $tag->id => ['flag' => 'taylor']]);
    $post->load('tags');
    $this->assertEquals(
        Tag::whereIn('id', [$tag->id])->pluck('name'),
        $post->tags->pluck('name')
    );
    $this->assertSame('taylor', $post->tags[0]->pivot->flag);
});

test('touching parent', function () {
    $post = Post::create(['title' => Str::random()]);

    $tag = TouchingTag::create(['name' => Str::random()]);

    $post->touchingTags()->attach([$tag->id]);

    $this->assertNotSame('2017-10-10 10:10:10', $post->fresh()->updated_at->toDateTimeString());

    Carbon::setTestNow('2017-10-10 10:10:10');

    $tag->update(['name' => $tag->name]);
    $this->assertNotSame('2017-10-10 10:10:10', $post->fresh()->updated_at->toDateTimeString());

    $tag->update(['name' => Str::random()]);
    $this->assertSame('2017-10-10 10:10:10', $post->fresh()->updated_at->toDateTimeString());
});

test('touching related models on sync', function () {
    $tag = TouchingTag::create(['name' => Str::random()]);

    $post = Post::create(['title' => Str::random()]);

    $this->assertNotSame('2017-10-10 10:10:10', $post->fresh()->updated_at->toDateTimeString());
    $this->assertNotSame('2017-10-10 10:10:10', $tag->fresh()->updated_at->toDateTimeString());

    Carbon::setTestNow('2017-10-10 10:10:10');

    $tag->posts()->sync([$post->id]);

    $this->assertSame('2017-10-10 10:10:10', $post->fresh()->updated_at->toDateTimeString());
    $this->assertSame('2017-10-10 10:10:10', $tag->fresh()->updated_at->toDateTimeString());
});

test('no touching happens if not configured', function () {
    $tag = Tag::create(['name' => Str::random()]);

    $post = Post::create(['title' => Str::random()]);

    $this->assertNotSame('2017-10-10 10:10:10', $post->fresh()->updated_at->toDateTimeString());
    $this->assertNotSame('2017-10-10 10:10:10', $tag->fresh()->updated_at->toDateTimeString());

    Carbon::setTestNow('2017-10-10 10:10:10');

    $tag->posts()->sync([$post->id]);

    $this->assertNotSame('2017-10-10 10:10:10', $post->fresh()->updated_at->toDateTimeString());
    $this->assertNotSame('2017-10-10 10:10:10', $tag->fresh()->updated_at->toDateTimeString());
});

/**/
test('can retrieve related ids', function () {
    $post = Post::create(['title' => Str::random()]);

    DB::table('tags')->insert([
        ['id' => 200, 'name' => 'excluded'],
        ['id' => 300, 'name' => Str::random()],
    ]);

    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => 200, 'flag' => ''],
        ['post_id' => $post->id, 'tag_id' => 300, 'flag' => 'exclude'],
        ['post_id' => $post->id, 'tag_id' => 400, 'flag' => ''],
    ]);

    $this->assertEquals([200, 400], $post->tags()->allRelatedIds()->toArray());
})->group('SkipMSSQL');

/**/
test('can touch related models', function () {
    $post = Post::create(['title' => Str::random()]);

    DB::table('tags')->insert([
        ['id' => 200, 'name' => Str::random()],
        ['id' => 300, 'name' => Str::random()],
    ]);

    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => 200, 'flag' => ''],
        ['post_id' => $post->id, 'tag_id' => 300, 'flag' => 'exclude'],
        ['post_id' => $post->id, 'tag_id' => 400, 'flag' => ''],
    ]);

    Carbon::setTestNow('2017-10-10 10:10:10');

    $post->tags()->touch();

    foreach ($post->tags()->pluck('tags.updated_at') as $date) {
        $this->assertSame('2017-10-10 10:10:10', $date);
    }

    $this->assertNotSame('2017-10-10 10:10:10', Tag::find(300)->updated_at);
})->group('SkipMSSQL');

/**/
test('where pivot on string', function () {
    $tag = Tag::create(['name' => Str::random()]);
    $post = Post::create(['title' => Str::random()]);

    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => $tag->id, 'flag' => 'foo'],
    ]);

    $relationTag = $post->tags()->wherePivot('flag', 'foo')->first();
    $this->assertEquals($relationTag->getAttributes(), $tag->getAttributes());

    $relationTag = $post->tags()->wherePivot('flag', '=', 'foo')->first();
    $this->assertEquals($relationTag->getAttributes(), $tag->getAttributes());
})->group('SkipMSSQL');

/**/
test('first where', function () {
    $tag = Tag::create(['name' => 'foo']);
    $post = Post::create(['title' => Str::random()]);

    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => $tag->id, 'flag' => 'foo'],
    ]);

    $relationTag = $post->tags()->firstWhere('name', 'foo');
    $this->assertEquals($relationTag->getAttributes(), $tag->getAttributes());

    $relationTag = $post->tags()->firstWhere('name', '=', 'foo');
    $this->assertEquals($relationTag->getAttributes(), $tag->getAttributes());
})->group('SkipMSSQL');

/**/
test('where pivot on boolean', function () {
    $tag = Tag::create(['name' => Str::random()]);
    $post = Post::create(['title' => Str::random()]);

    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => $tag->id, 'flag' => true],
    ]);

    $relationTag = $post->tags()->wherePivot('flag', true)->first();
    $this->assertEquals($relationTag->getAttributes(), $tag->getAttributes());

    $relationTag = $post->tags()->wherePivot('flag', '=', true)->first();
    $this->assertEquals($relationTag->getAttributes(), $tag->getAttributes());
})->group('SkipMSSQL');

/**/
test('where pivot in method', function () {
    $tag = Tag::create(['name' => Str::random()]);
    $post = Post::create(['title' => Str::random()]);

    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => $tag->id, 'flag' => 'foo'],
    ]);

    $relationTag = $post->tags()->wherePivotIn('flag', ['foo'])->first();
    $this->assertEquals($relationTag->getAttributes(), $tag->getAttributes());
})->group('SkipMSSQL');

test('or where pivot in method', function () {
    $tag1 = Tag::create(['name' => Str::random()]);
    $tag2 = Tag::create(['name' => Str::random()]);
    $tag3 = Tag::create(['name' => Str::random()]);
    $post = Post::create(['title' => Str::random()]);

    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => $tag1->id, 'flag' => 'foo'],
    ]);
    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => $tag2->id, 'flag' => 'bar'],
    ]);
    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => $tag3->id, 'flag' => 'baz'],
    ]);

    $relationTags = $post->tags()->wherePivotIn('flag', ['foo'])->orWherePivotIn('flag', ['baz'])->get();
    $this->assertEquals($relationTags->pluck('id')->toArray(), [$tag1->id, $tag3->id]);
});

/**/
test('where pivot not in method', function () {
    $tag1 = Tag::create(['name' => Str::random()]);
    $tag2 = Tag::create(['name' => Str::random()]);
    $post = Post::create(['title' => Str::random()]);

    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => $tag1->id, 'flag' => 'foo'],
    ]);
    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => $tag2->id, 'flag' => 'bar'],
    ]);

    $relationTag = $post->tags()->wherePivotNotIn('flag', ['foo'])->first();
    $this->assertEquals($relationTag->getAttributes(), $tag2->getAttributes());
})->group('SkipMSSQL');

test('or where pivot not in method', function () {
    $tag1 = Tag::create(['name' => Str::random()]);
    $tag2 = Tag::create(['name' => Str::random()]);
    $tag3 = Tag::create(['name' => Str::random()]);
    $post = Post::create(['title' => Str::random()]);

    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => $tag1->id, 'flag' => 'foo'],
    ]);
    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => $tag2->id, 'flag' => 'bar'],
    ]);
    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => $tag3->id, 'flag' => 'baz'],
    ]);

    $relationTags = $post->tags()->wherePivotIn('flag', ['foo'])->orWherePivotNotIn('flag', ['baz'])->get();
    $this->assertEquals($relationTags->pluck('id')->toArray(), [$tag1->id, $tag2->id]);
});

/**/
test('where pivot null method', function () {
    $tag1 = Tag::create(['name' => Str::random()]);
    $tag2 = Tag::create(['name' => Str::random()]);
    $post = Post::create(['title' => Str::random()]);

    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => $tag1->id, 'flag' => 'foo'],
    ]);
    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => $tag2->id, 'flag' => null],
    ]);

    $relationTag = $post->tagsWithExtraPivot()->wherePivotNull('flag')->first();
    $this->assertEquals($relationTag->getAttributes(), $tag2->getAttributes());
})->group('SkipMSSQL');

/**/
test('where pivot not null method', function () {
    $tag1 = Tag::create(['name' => Str::random()]);
    $tag2 = Tag::create(['name' => Str::random()]);
    $post = Post::create(['title' => Str::random()]);

    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => $tag1->id, 'flag' => 'foo'],
    ]);
    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => $tag2->id, 'flag' => null],
    ]);

    $relationTag = $post->tagsWithExtraPivot()->wherePivotNotNull('flag')->first();
    $this->assertEquals($relationTag->getAttributes(), $tag1->getAttributes());
})->group('SkipMSSQL');

test('can update existing pivot', function () {
    $tag = Tag::create(['name' => Str::random()]);
    $post = Post::create(['title' => Str::random()]);

    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => $tag->id, 'flag' => 'empty'],
    ]);

    $post->tagsWithExtraPivot()->updateExistingPivot($tag->id, ['flag' => 'exclude']);

    foreach ($post->tagsWithExtraPivot as $tag) {
        $this->assertSame('exclude', $tag->pivot->flag);
    }
});

test('can update existing pivot using arrayable of ids', function () {
    $tags = new Collection([
        $tag1 = Tag::create(['name' => Str::random()]),
        $tag2 = Tag::create(['name' => Str::random()]),
    ]);
    $post = Post::create(['title' => Str::random()]);

    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => $tag1->id, 'flag' => 'empty'],
        ['post_id' => $post->id, 'tag_id' => $tag2->id, 'flag' => 'empty'],
    ]);

    $post->tagsWithExtraPivot()->updateExistingPivot($tags, ['flag' => 'exclude']);

    foreach ($post->tagsWithExtraPivot as $tag) {
        $this->assertSame('exclude', $tag->pivot->flag);
    }
});

test('can update existing pivot using model', function () {
    $tag = Tag::create(['name' => Str::random()]);
    $post = Post::create(['title' => Str::random()]);

    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => $tag->id, 'flag' => 'empty'],
    ]);

    $post->tagsWithExtraPivot()->updateExistingPivot($tag, ['flag' => 'exclude']);

    foreach ($post->tagsWithExtraPivot as $tag) {
        $this->assertSame('exclude', $tag->pivot->flag);
    }
});

test('custom related key', function () {
    $post = Post::create(['title' => Str::random()]);

    $tag = $post->tagsWithCustomRelatedKey()->create(['name' => Str::random()]);
    $this->assertEquals($tag->name, $post->tagsWithCustomRelatedKey()->first()->pivot->tag_name);

    $post->tagsWithCustomRelatedKey()->detach($tag);

    $post->tagsWithCustomRelatedKey()->attach($tag);
    $this->assertEquals($tag->name, $post->tagsWithCustomRelatedKey()->first()->pivot->tag_name);

    $post->tagsWithCustomRelatedKey()->detach(new Collection([$tag]));

    $post->tagsWithCustomRelatedKey()->attach(new Collection([$tag]));
    $this->assertEquals($tag->name, $post->tagsWithCustomRelatedKey()->first()->pivot->tag_name);

    $post->tagsWithCustomRelatedKey()->updateExistingPivot($tag, ['flag' => 'exclude']);
    $this->assertSame('exclude', $post->tagsWithCustomRelatedKey()->first()->pivot->flag);
});

test('global scope columns', function () {
    $tag = Tag::create(['id' => '1', 'name' => Str::random()]);
    $post = Post::create(['id' => '2', 'title' => Str::random()]);

    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => $tag->id, 'flag' => 'empty'],
    ]);

    $tags = $post->tagsWithGlobalScope;

    $this->assertEquals(['id' => 1], $tags[0]->getAttributes());
});

test('pivot doesnt have primary key', function () {
    $user = UserBelongsToMany::create(['name' => Str::random()]);
    $post1 = Post::create(['title' => Str::random()]);
    $post2 = Post::create(['title' => Str::random()]);

    $user->postsWithCustomPivot()->sync([$post1->uuid]);
    $this->assertEquals($user->uuid, $user->postsWithCustomPivot()->first()->pivot->user_uuid);
    $this->assertEquals($post1->uuid, $user->postsWithCustomPivot()->first()->pivot->post_uuid);
    $this->assertEquals(1, $user->postsWithCustomPivot()->first()->pivot->is_draft);

    $user->postsWithCustomPivot()->sync([$post2->uuid]);
    $this->assertEquals($user->uuid, $user->postsWithCustomPivot()->first()->pivot->user_uuid);
    $this->assertEquals($post2->uuid, $user->postsWithCustomPivot()->first()->pivot->post_uuid);
    $this->assertEquals(1, $user->postsWithCustomPivot()->first()->pivot->is_draft);

    $user->postsWithCustomPivot()->updateExistingPivot($post2->uuid, ['is_draft' => 0]);
    $this->assertEquals(0, $user->postsWithCustomPivot()->first()->pivot->is_draft);
});

/**/
test('order by pivot method', function () {
    $tag1 = Tag::create(['name' => Str::random()]);
    $tag2 = Tag::create(['name' => Str::random()]);
    $tag3 = Tag::create(['name' => Str::random()]);
    $tag4 = Tag::create(['name' => Str::random()]);
    $post = Post::create(['title' => Str::random()]);

    DB::table('posts_tags')->insert([
        ['post_id' => $post->id, 'tag_id' => $tag1->id, 'flag' => 'foo3'],
        ['post_id' => $post->id, 'tag_id' => $tag2->id, 'flag' => 'foo1'],
        ['post_id' => $post->id, 'tag_id' => $tag3->id, 'flag' => 'foo4'],
        ['post_id' => $post->id, 'tag_id' => $tag4->id, 'flag' => 'foo2'],
    ]);

    $relationTag1 = $post->tagsWithCustomExtraPivot()->orderByPivot('flag', 'asc')->first();
    $this->assertEquals($relationTag1->getAttributes(), $tag2->getAttributes());

    $relationTag2 = $post->tagsWithCustomExtraPivot()->orderByPivot('flag', 'desc')->first();
    $this->assertEquals($relationTag2->getAttributes(), $tag3->getAttributes());
})->group('SkipMSSQL');

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('uuid');
        $table->string('name');
        $table->timestamps();
    });

    Schema::create('posts', function (Blueprint $table) {
        $table->increments('id');
        $table->string('uuid');
        $table->string('title');
        $table->timestamps();
    });

    Schema::create('tags', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->timestamps();
    });

    Schema::create('users_posts', function (Blueprint $table) {
        $table->string('user_uuid');
        $table->string('post_uuid');
        $table->tinyInteger('is_draft')->default(1);
        $table->timestamps();
    });

    Schema::create('posts_tags', function (Blueprint $table) {
        $table->integer('post_id');
        $table->integer('tag_id')->default(0);
        $table->string('tag_name')->default('')->nullable();
        $table->string('flag')->default('')->nullable();
        $table->timestamps();
    });
}

function postsWithCustomPivot()
{
    return test()->belongsToMany(Post::class, 'users_posts', 'user_uuid', 'post_uuid', 'uuid', 'uuid')
        ->using(UserBelongsToManyPostPivot::class)
        ->withPivot('is_draft')
        ->withTimestamps();
}

function tags()
{
    return test()->belongsToMany(Tag::class, 'posts_tags', 'post_id', 'tag_id')
        ->withPivot('flag')
        ->withTimestamps()
        ->wherePivot('flag', '<>', 'exclude');
}

function tagsWithExtraPivot()
{
    return test()->belongsToMany(Tag::class, 'posts_tags', 'post_id', 'tag_id')
        ->withPivot('flag');
}

function touchingTags()
{
    return test()->belongsToMany(TouchingTag::class, 'posts_tags', 'post_id', 'tag_id')
        ->withTimestamps();
}

function tagsWithCustomPivot()
{
    return test()->belongsToMany(TagWithCustomPivot::class, 'posts_tags', 'post_id', 'tag_id')
        ->using(PostTagPivot::class)
        ->withTimestamps();
}

function tagsWithCustomExtraPivot()
{
    return test()->belongsToMany(TagWithCustomPivot::class, 'posts_tags', 'post_id', 'tag_id')
        ->using(PostTagPivot::class)
        ->withTimestamps()
        ->withPivot('flag');
}

function tagsWithCustomPivotClass()
{
    return test()->belongsToMany(TagWithCustomPivot::class, PostTagPivot::class, 'post_id', 'tag_id');
}

function tagsWithCustomAccessor()
{
    return test()->belongsToMany(TagWithCustomPivot::class, 'posts_tags', 'post_id', 'tag_id')
        ->using(PostTagPivot::class)
        ->as('tag');
}

function tagsWithCustomRelatedKey()
{
    return test()->belongsToMany(Tag::class, 'posts_tags', 'post_id', 'tag_name', 'id', 'name')
        ->withPivot('flag');
}

function tagsWithGlobalScope()
{
    return test()->belongsToMany(TagWithGlobalScope::class, 'posts_tags', 'post_id', 'tag_id');
}

function posts()
{
    return test()->belongsToMany(Post::class, 'posts_tags', 'tag_id', 'post_id');
}

function getCreatedAtAttribute($value)
{
    return Carbon::parse($value)->format('U');
}

function boot()
{
    parent::boot();

    static::addGlobalScope(function ($query) {
        $query->select('tags.id');
    });
}

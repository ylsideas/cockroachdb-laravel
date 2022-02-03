<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

test('lock can have a separate connection', function () {
    app()['config']->set('cache.stores.database.lock_connection', 'test');
    app()['config']->set('database.connections.test', app()['config']->get('database.connections.mysql'));

    $this->assertSame('test', Cache::driver('database')->lock('foo')->getConnectionName());
});

test('lock can be acquired', function () {
    $lock = Cache::driver('database')->lock('foo');
    $this->assertTrue($lock->get());

    $otherLock = Cache::driver('database')->lock('foo');
    $this->assertFalse($otherLock->get());

    $lock->release();

    $otherLock = Cache::driver('database')->lock('foo');
    $this->assertTrue($otherLock->get());

    $otherLock->release();
});

test('lock can be force released', function () {
    $lock = Cache::driver('database')->lock('foo');
    $this->assertTrue($lock->get());

    $otherLock = Cache::driver('database')->lock('foo');
    $otherLock->forceRelease();
    $this->assertTrue($otherLock->get());

    $otherLock->release();
});

test('expired lock can be retrieved', function () {
    $lock = Cache::driver('database')->lock('foo');
    $this->assertTrue($lock->get());
    DB::table('cache_locks')->update(['expiration' => now()->subDays(1)->getTimestamp()]);

    $otherLock = Cache::driver('database')->lock('foo');
    $this->assertTrue($otherLock->get());

    $otherLock->release();
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('cache_locks', function (Blueprint $table) {
        $table->string('key')->primary();
        $table->string('owner');
        $table->integer('expiration');
    });
}

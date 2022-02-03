<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

test('lock can have a separate connection', function () {
    app()['config']->set('cache.stores.database.lock_connection', 'test');
    app()['config']->set('database.connections.test', app()['config']->get('database.connections.mysql'));

    expect(Cache::driver('database')->lock('foo')->getConnectionName())->toBe('test');
});

test('lock can be acquired', function () {
    $lock = Cache::driver('database')->lock('foo');
    expect($lock->get())->toBeTrue();

    $otherLock = Cache::driver('database')->lock('foo');
    expect($otherLock->get())->toBeFalse();

    $lock->release();

    $otherLock = Cache::driver('database')->lock('foo');
    expect($otherLock->get())->toBeTrue();

    $otherLock->release();
});

test('lock can be force released', function () {
    $lock = Cache::driver('database')->lock('foo');
    expect($lock->get())->toBeTrue();

    $otherLock = Cache::driver('database')->lock('foo');
    $otherLock->forceRelease();
    expect($otherLock->get())->toBeTrue();

    $otherLock->release();
});

test('expired lock can be retrieved', function () {
    $lock = Cache::driver('database')->lock('foo');
    expect($lock->get())->toBeTrue();
    DB::table('cache_locks')->update(['expiration' => now()->subDays(1)->getTimestamp()]);

    $otherLock = Cache::driver('database')->lock('foo');
    expect($otherLock->get())->toBeTrue();

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

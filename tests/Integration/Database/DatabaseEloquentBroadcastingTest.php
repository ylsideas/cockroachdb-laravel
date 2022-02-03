<?php

use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastingFactory;
use Illuminate\Database\Eloquent\BroadcastableModelEventOccurred;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);
use Mockery as m;

test('basic broadcasting', function () {
    Event::fake([BroadcastableModelEventOccurred::class]);

    $model = new TestEloquentBroadcastUser();
    $model->name = 'Taylor';
    $model->save();

    Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
        return $event->model instanceof TestEloquentBroadcastUser
                && count($event->broadcastOn()) === 1
                && $event->model->name === 'Taylor'
                && $event->broadcastOn()[0]->name == "private-YlsIdeas.CockroachDb.Tests.Integration.Database.TestEloquentBroadcastUser.{$event->model->id}";
    });
});

test('channel route formatting', function () {
    $model = new TestEloquentBroadcastUser();

    expect($model->broadcastChannelRoute())->toEqual('YlsIdeas.CockroachDb.Tests.Integration.Database.TestEloquentBroadcastUser.{testEloquentBroadcastUser}');
});

test('broadcasting on model trashing', function () {
    Event::fake([BroadcastableModelEventOccurred::class]);

    $model = new SoftDeletableTestEloquentBroadcastUser();
    $model->name = 'Bean';
    $model->saveQuietly();

    $model->delete();

    Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
        return $event->model instanceof SoftDeletableTestEloquentBroadcastUser
            && $event->event() == 'trashed'
            && count($event->broadcastOn()) === 1
            && $event->model->name === 'Bean'
            && $event->broadcastOn()[0]->name == "private-YlsIdeas.CockroachDb.Tests.Integration.Database.SoftDeletableTestEloquentBroadcastUser.{$event->model->id}";
    });
});

test('broadcasting for specific events only', function () {
    Event::fake([BroadcastableModelEventOccurred::class]);

    $model = new TestEloquentBroadcastUserOnSpecificEventsOnly();
    $model->name = 'James';
    $model->save();

    Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
        return $event->model instanceof TestEloquentBroadcastUserOnSpecificEventsOnly
            && $event->event() == 'created'
            && count($event->broadcastOn()) === 1
            && $event->model->name === 'James'
            && $event->broadcastOn()[0]->name == "private-YlsIdeas.CockroachDb.Tests.Integration.Database.TestEloquentBroadcastUserOnSpecificEventsOnly.{$event->model->id}";
    });

    $model->name = 'Graham';
    $model->save();

    Event::assertNotDispatched(function (BroadcastableModelEventOccurred $event) {
        return $event->model instanceof TestEloquentBroadcastUserOnSpecificEventsOnly
            && $event->model->name === 'Graham'
            && $event->event() == 'updated';
    });
});

test('broadcast name default', function () {
    Event::fake([BroadcastableModelEventOccurred::class]);

    $model = new TestEloquentBroadcastUser();
    $model->name = 'Mohamed';
    $model->save();

    Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
        return $event->model instanceof TestEloquentBroadcastUser
            && $event->model->name === 'Mohamed'
            && $event->broadcastAs() === 'TestEloquentBroadcastUserCreated'
            && assertHandldedBroadcastableEvent($event, function (array $channels, string $eventName, array $payload) {
                return $eventName === 'TestEloquentBroadcastUserCreated';
            });
    });
});

test('broadcast name can be defined', function () {
    Event::fake([BroadcastableModelEventOccurred::class]);

    $model = new TestEloquentBroadcastUserWithSpecificBroadcastName();
    $model->name = 'Nuno';
    $model->save();

    Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
        return $event->model instanceof TestEloquentBroadcastUserWithSpecificBroadcastName
            && $event->model->name === 'Nuno'
            && $event->broadcastAs() === 'foo'
            && assertHandldedBroadcastableEvent($event, function (array $channels, string $eventName, array $payload) {
                return $eventName === 'foo';
            });
    });

    $model->name = 'Dries';
    $model->save();

    Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
        return $event->model instanceof TestEloquentBroadcastUserWithSpecificBroadcastName
            && $event->model->name === 'Dries'
            && $event->broadcastAs() === 'TestEloquentBroadcastUserWithSpecificBroadcastNameUpdated'
            && assertHandldedBroadcastableEvent($event, function (array $channels, string $eventName, array $payload) {
                return $eventName === 'TestEloquentBroadcastUserWithSpecificBroadcastNameUpdated';
            });
    });
});

test('broadcast payload default', function () {
    Event::fake([BroadcastableModelEventOccurred::class]);

    $model = new TestEloquentBroadcastUser();
    $model->name = 'Nuno';
    $model->save();

    Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
        return $event->model instanceof TestEloquentBroadcastUser
            && $event->model->name === 'Nuno'
            && is_null($event->broadcastWith())
            && assertHandldedBroadcastableEvent($event, function (array $channels, string $eventName, array $payload) {
                return Arr::has($payload, ['model', 'connection', 'queue', 'socket']);
            });
    });
});

test('broadcast payload can be defined', function () {
    Event::fake([BroadcastableModelEventOccurred::class]);

    $model = new TestEloquentBroadcastUserWithSpecificBroadcastPayload();
    $model->name = 'Dries';
    $model->save();

    Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
        return $event->model instanceof TestEloquentBroadcastUserWithSpecificBroadcastPayload
            && $event->model->name === 'Dries'
            && $event->broadcastWith() === ['foo' => 'bar']
            && assertHandldedBroadcastableEvent($event, function (array $channels, string $eventName, array $payload) {
                return Arr::has($payload, ['foo', 'socket']);
            });
    });

    $model->name = 'Graham';
    $model->save();

    Event::assertDispatched(function (BroadcastableModelEventOccurred $event) {
        return $event->model instanceof TestEloquentBroadcastUserWithSpecificBroadcastPayload
            && $event->model->name === 'Graham'
            && is_null($event->broadcastWith())
            && assertHandldedBroadcastableEvent($event, function (array $channels, string $eventName, array $payload) {
                return Arr::has($payload, ['model', 'connection', 'queue', 'socket']);
            });
    });
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('test_eloquent_broadcasting_users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->softDeletes();
        $table->timestamps();
    });
}

function assertHandldedBroadcastableEvent(BroadcastableModelEventOccurred $event, \Closure $closure)
{
    $broadcaster = m::mock(Broadcaster::class);
    $broadcaster->shouldReceive('broadcast')->once()
        ->withArgs(function (array $channels, string $eventName, array $payload) use ($closure) {
            return $closure($channels, $eventName, $payload);
        });

    $manager = m::mock(BroadcastingFactory::class);
    $manager->shouldReceive('connection')->once()->with(null)->andReturn($broadcaster);

    (new BroadcastEvent($event))->handle($manager);

    return true;
}

function broadcastOn($event)
{
    switch ($event) {
        case 'created':
            return [$this];
    }
}

function broadcastAs($event)
{
    switch ($event) {
        case 'created':
            return 'foo';
    }
}

function broadcastWith($event)
{
    switch ($event) {
        case 'created':
            return ['foo' => 'bar'];
    }
}

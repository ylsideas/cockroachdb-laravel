<?php

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Contracts\Database\Eloquent\DeviatesCastableAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\InvalidCastException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

test('basic custom casting', function () {
    $model = new TestEloquentModelWithCustomCast();
    $model->uppercase = 'taylor';

    $this->assertSame('TAYLOR', $model->uppercase);
    $this->assertSame('TAYLOR', $model->getAttributes()['uppercase']);
    $this->assertSame('TAYLOR', $model->toArray()['uppercase']);

    $unserializedModel = unserialize(serialize($model));

    $this->assertSame('TAYLOR', $unserializedModel->uppercase);
    $this->assertSame('TAYLOR', $unserializedModel->getAttributes()['uppercase']);
    $this->assertSame('TAYLOR', $unserializedModel->toArray()['uppercase']);

    $model->syncOriginal();
    $model->uppercase = 'dries';
    $this->assertSame('TAYLOR', $model->getOriginal('uppercase'));

    $model = new TestEloquentModelWithCustomCast();
    $model->uppercase = 'taylor';
    $model->syncOriginal();
    $model->uppercase = 'dries';
    $model->getOriginal();

    $this->assertSame('DRIES', $model->uppercase);

    $model = new TestEloquentModelWithCustomCast();

    $model->address = $address = new Address('110 Kingsbrook St.', 'My Childhood House');
    $address->lineOne = '117 Spencer St.';
    $this->assertSame('117 Spencer St.', $model->getAttributes()['address_line_one']);

    $model = new TestEloquentModelWithCustomCast();

    $model->setRawAttributes([
        'address_line_one' => '110 Kingsbrook St.',
        'address_line_two' => 'My Childhood House',
    ]);

    $this->assertSame('110 Kingsbrook St.', $model->address->lineOne);
    $this->assertSame('My Childhood House', $model->address->lineTwo);

    $this->assertSame('110 Kingsbrook St.', $model->toArray()['address_line_one']);
    $this->assertSame('My Childhood House', $model->toArray()['address_line_two']);

    $model->address->lineOne = '117 Spencer St.';

    $this->assertFalse(isset($model->toArray()['address']));
    $this->assertSame('117 Spencer St.', $model->toArray()['address_line_one']);
    $this->assertSame('My Childhood House', $model->toArray()['address_line_two']);

    $this->assertSame('117 Spencer St.', json_decode($model->toJson(), true)['address_line_one']);
    $this->assertSame('My Childhood House', json_decode($model->toJson(), true)['address_line_two']);

    $model->address = null;

    $this->assertNull($model->toArray()['address_line_one']);
    $this->assertNull($model->toArray()['address_line_two']);

    $model->options = ['foo' => 'bar'];
    $this->assertEquals(['foo' => 'bar'], $model->options);
    $this->assertEquals(['foo' => 'bar'], $model->options);
    $model->options = ['foo' => 'bar'];
    $model->options = ['foo' => 'bar'];
    $this->assertEquals(['foo' => 'bar'], $model->options);
    $this->assertEquals(['foo' => 'bar'], $model->options);

    $this->assertSame(json_encode(['foo' => 'bar']), $model->getAttributes()['options']);

    $model = new TestEloquentModelWithCustomCast(['options' => []]);
    $model->syncOriginal();
    $model->options = ['foo' => 'bar'];
    $this->assertTrue($model->isDirty('options'));

    $model = new TestEloquentModelWithCustomCast();
    $model->birthday_at = now();
    $this->assertIsString($model->toArray()['birthday_at']);
});

test('get original with cast value objects', function () {
    $model = new TestEloquentModelWithCustomCast([
        'address' => new Address('110 Kingsbrook St.', 'My Childhood House'),
    ]);

    $model->syncOriginal();

    $model->address = new Address('117 Spencer St.', 'Another house.');

    $this->assertSame('117 Spencer St.', $model->address->lineOne);
    $this->assertSame('110 Kingsbrook St.', $model->getOriginal('address')->lineOne);
    $this->assertSame('117 Spencer St.', $model->address->lineOne);

    $model = new TestEloquentModelWithCustomCast([
        'address' => new Address('110 Kingsbrook St.', 'My Childhood House'),
    ]);

    $model->syncOriginal();

    $model->address = new Address('117 Spencer St.', 'Another house.');

    $this->assertSame('117 Spencer St.', $model->address->lineOne);
    $this->assertSame('110 Kingsbrook St.', $model->getOriginal()['address_line_one']);
    $this->assertSame('117 Spencer St.', $model->address->lineOne);
    $this->assertSame('110 Kingsbrook St.', $model->getOriginal()['address_line_one']);

    $model = new TestEloquentModelWithCustomCast([
        'address' => new Address('110 Kingsbrook St.', 'My Childhood House'),
    ]);

    $model->syncOriginal();

    $model->address = null;

    $this->assertNull($model->address);
    $this->assertInstanceOf(Address::class, $model->getOriginal('address'));
    $this->assertNull($model->address);
});

test('deviable casts', function () {
    $model = new TestEloquentModelWithCustomCast();
    $model->price = '123.456';
    $model->save();

    $model->increment('price', '530.865');

    $this->assertSame((new Decimal('654.321'))->getValue(), $model->price->getValue());

    $model->decrement('price', '333.333');

    $this->assertSame((new Decimal('320.988'))->getValue(), $model->price->getValue());
});

test('serializable casts', function () {
    $model = new TestEloquentModelWithCustomCast();
    $model->price = '123.456';

    $expectedValue = (new Decimal('123.456'))->getValue();

    $this->assertSame($expectedValue, $model->price->getValue());
    $this->assertSame('123.456', $model->getAttributes()['price']);
    $this->assertSame('123.456', $model->toArray()['price']);

    $unserializedModel = unserialize(serialize($model));

    $this->assertSame($expectedValue, $unserializedModel->price->getValue());
    $this->assertSame('123.456', $unserializedModel->getAttributes()['price']);
    $this->assertSame('123.456', $unserializedModel->toArray()['price']);
});

test('one way casting', function () {
    // CastsInboundAttributes is used for casting that is unidirectional... only use case I can think of is one-way hashing...
    $model = new TestEloquentModelWithCustomCast();

    $model->password = 'secret';

    $this->assertEquals(hash('sha256', 'secret'), $model->password);
    $this->assertEquals(hash('sha256', 'secret'), $model->getAttributes()['password']);
    $this->assertEquals(hash('sha256', 'secret'), $model->getAttributes()['password']);
    $this->assertEquals(hash('sha256', 'secret'), $model->password);

    $model->password = 'secret2';

    $this->assertEquals(hash('sha256', 'secret2'), $model->password);
    $this->assertEquals(hash('sha256', 'secret2'), $model->getAttributes()['password']);
    $this->assertEquals(hash('sha256', 'secret2'), $model->getAttributes()['password']);
    $this->assertEquals(hash('sha256', 'secret2'), $model->password);
});

test('setting raw attributes clears the cast cache', function () {
    $model = new TestEloquentModelWithCustomCast();

    $model->setRawAttributes([
        'address_line_one' => '110 Kingsbrook St.',
        'address_line_two' => 'My Childhood House',
    ]);

    $this->assertSame('110 Kingsbrook St.', $model->address->lineOne);

    $model->setRawAttributes([
        'address_line_one' => '117 Spencer St.',
        'address_line_two' => 'My Childhood House',
    ]);

    $this->assertSame('117 Spencer St.', $model->address->lineOne);
});

test('with castable interface', function () {
    $model = new TestEloquentModelWithCustomCast();

    $model->setRawAttributes([
        'value_object_with_caster' => serialize(new ValueObject('hello')),
    ]);

    $this->assertInstanceOf(ValueObject::class, $model->value_object_with_caster);
    $this->assertSame(serialize(new ValueObject('hello')), $model->toArray()['value_object_with_caster']);

    $model->setRawAttributes([
        'value_object_caster_with_argument' => null,
    ]);

    $this->assertSame('argument', $model->value_object_caster_with_argument);

    $model->setRawAttributes([
        'value_object_caster_with_caster_instance' => serialize(new ValueObject('hello')),
    ]);

    $this->assertInstanceOf(ValueObject::class, $model->value_object_caster_with_caster_instance);
});

test('get from undefined cast', function () {
    $this->expectException(InvalidCastException::class);

    $model = new TestEloquentModelWithCustomCast();
    $model->undefined_cast_column;
});

test('set to undefined cast', function () {
    $this->expectException(InvalidCastException::class);

    $model = new TestEloquentModelWithCustomCast();
    $this->assertTrue($model->hasCast('undefined_cast_column'));

    $model->undefined_cast_column = 'Glāžšķūņu rūķīši';
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('test_eloquent_model_with_custom_casts', function (Blueprint $table) {
        $table->increments('id');
        $table->timestamps();
        $table->decimal('price');
    });
}

function increment($model, $key, $value, $attributes)
{
    return new Decimal($attributes[$key] + $value);
}

function decrement($model, $key, $value, $attributes)
{
    return new Decimal($attributes[$key] - $value);
}

function serialize($model, $key, $value, $attributes)
{
    return serialize($value);
}

function castUsing(array $arguments)
{
    return new ValueObjectCaster();
}

function getValue()
{
    return test()->value;
}

function __toString()
{
    return substr_replace(test()->value, '.', -test()->scale, 0);
}

function __construct($argument = null)
{
    test()->argument = $argument;
}

function get($model, $key, $value, $attributes)
{
    return Carbon::parse($value);
}

function set($model, $key, $value, $attributes)
{
    return $value->format('Y-m-d');
}

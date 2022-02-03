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

    expect($model->uppercase)->toBe('TAYLOR');
    expect($model->getAttributes()['uppercase'])->toBe('TAYLOR');
    expect($model->toArray()['uppercase'])->toBe('TAYLOR');

    $unserializedModel = unserialize(serialize($model));

    expect($unserializedModel->uppercase)->toBe('TAYLOR');
    expect($unserializedModel->getAttributes()['uppercase'])->toBe('TAYLOR');
    expect($unserializedModel->toArray()['uppercase'])->toBe('TAYLOR');

    $model->syncOriginal();
    $model->uppercase = 'dries';
    expect($model->getOriginal('uppercase'))->toBe('TAYLOR');

    $model = new TestEloquentModelWithCustomCast();
    $model->uppercase = 'taylor';
    $model->syncOriginal();
    $model->uppercase = 'dries';
    $model->getOriginal();

    expect($model->uppercase)->toBe('DRIES');

    $model = new TestEloquentModelWithCustomCast();

    $model->address = $address = new Address('110 Kingsbrook St.', 'My Childhood House');
    $address->lineOne = '117 Spencer St.';
    expect($model->getAttributes()['address_line_one'])->toBe('117 Spencer St.');

    $model = new TestEloquentModelWithCustomCast();

    $model->setRawAttributes([
        'address_line_one' => '110 Kingsbrook St.',
        'address_line_two' => 'My Childhood House',
    ]);

    expect($model->address->lineOne)->toBe('110 Kingsbrook St.');
    expect($model->address->lineTwo)->toBe('My Childhood House');

    expect($model->toArray()['address_line_one'])->toBe('110 Kingsbrook St.');
    expect($model->toArray()['address_line_two'])->toBe('My Childhood House');

    $model->address->lineOne = '117 Spencer St.';

    expect(isset($model->toArray()['address']))->toBeFalse();
    expect($model->toArray()['address_line_one'])->toBe('117 Spencer St.');
    expect($model->toArray()['address_line_two'])->toBe('My Childhood House');

    expect(json_decode($model->toJson(), true)['address_line_one'])->toBe('117 Spencer St.');
    expect(json_decode($model->toJson(), true)['address_line_two'])->toBe('My Childhood House');

    $model->address = null;

    expect($model->toArray()['address_line_one'])->toBeNull();
    expect($model->toArray()['address_line_two'])->toBeNull();

    $model->options = ['foo' => 'bar'];
    expect($model->options)->toEqual(['foo' => 'bar']);
    expect($model->options)->toEqual(['foo' => 'bar']);
    $model->options = ['foo' => 'bar'];
    $model->options = ['foo' => 'bar'];
    expect($model->options)->toEqual(['foo' => 'bar']);
    expect($model->options)->toEqual(['foo' => 'bar']);

    expect($model->getAttributes()['options'])->toBe(json_encode(['foo' => 'bar']));

    $model = new TestEloquentModelWithCustomCast(['options' => []]);
    $model->syncOriginal();
    $model->options = ['foo' => 'bar'];
    expect($model->isDirty('options'))->toBeTrue();

    $model = new TestEloquentModelWithCustomCast();
    $model->birthday_at = now();
    expect($model->toArray()['birthday_at'])->toBeString();
});

test('get original with cast value objects', function () {
    $model = new TestEloquentModelWithCustomCast([
        'address' => new Address('110 Kingsbrook St.', 'My Childhood House'),
    ]);

    $model->syncOriginal();

    $model->address = new Address('117 Spencer St.', 'Another house.');

    expect($model->address->lineOne)->toBe('117 Spencer St.');
    expect($model->getOriginal('address')->lineOne)->toBe('110 Kingsbrook St.');
    expect($model->address->lineOne)->toBe('117 Spencer St.');

    $model = new TestEloquentModelWithCustomCast([
        'address' => new Address('110 Kingsbrook St.', 'My Childhood House'),
    ]);

    $model->syncOriginal();

    $model->address = new Address('117 Spencer St.', 'Another house.');

    expect($model->address->lineOne)->toBe('117 Spencer St.');
    expect($model->getOriginal()['address_line_one'])->toBe('110 Kingsbrook St.');
    expect($model->address->lineOne)->toBe('117 Spencer St.');
    expect($model->getOriginal()['address_line_one'])->toBe('110 Kingsbrook St.');

    $model = new TestEloquentModelWithCustomCast([
        'address' => new Address('110 Kingsbrook St.', 'My Childhood House'),
    ]);

    $model->syncOriginal();

    $model->address = null;

    expect($model->address)->toBeNull();
    expect($model->getOriginal('address'))->toBeInstanceOf(Address::class);
    expect($model->address)->toBeNull();
});

test('deviable casts', function () {
    $model = new TestEloquentModelWithCustomCast();
    $model->price = '123.456';
    $model->save();

    $model->increment('price', '530.865');

    expect($model->price->getValue())->toBe((new Decimal('654.321'))->getValue());

    $model->decrement('price', '333.333');

    expect($model->price->getValue())->toBe((new Decimal('320.988'))->getValue());
});

test('serializable casts', function () {
    $model = new TestEloquentModelWithCustomCast();
    $model->price = '123.456';

    $expectedValue = (new Decimal('123.456'))->getValue();

    expect($model->price->getValue())->toBe($expectedValue);
    expect($model->getAttributes()['price'])->toBe('123.456');
    expect($model->toArray()['price'])->toBe('123.456');

    $unserializedModel = unserialize(serialize($model));

    expect($unserializedModel->price->getValue())->toBe($expectedValue);
    expect($unserializedModel->getAttributes()['price'])->toBe('123.456');
    expect($unserializedModel->toArray()['price'])->toBe('123.456');
});

test('one way casting', function () {
    // CastsInboundAttributes is used for casting that is unidirectional... only use case I can think of is one-way hashing...
    $model = new TestEloquentModelWithCustomCast();

    $model->password = 'secret';

    expect($model->password)->toEqual(hash('sha256', 'secret'));
    expect($model->getAttributes()['password'])->toEqual(hash('sha256', 'secret'));
    expect($model->getAttributes()['password'])->toEqual(hash('sha256', 'secret'));
    expect($model->password)->toEqual(hash('sha256', 'secret'));

    $model->password = 'secret2';

    expect($model->password)->toEqual(hash('sha256', 'secret2'));
    expect($model->getAttributes()['password'])->toEqual(hash('sha256', 'secret2'));
    expect($model->getAttributes()['password'])->toEqual(hash('sha256', 'secret2'));
    expect($model->password)->toEqual(hash('sha256', 'secret2'));
});

test('setting raw attributes clears the cast cache', function () {
    $model = new TestEloquentModelWithCustomCast();

    $model->setRawAttributes([
        'address_line_one' => '110 Kingsbrook St.',
        'address_line_two' => 'My Childhood House',
    ]);

    expect($model->address->lineOne)->toBe('110 Kingsbrook St.');

    $model->setRawAttributes([
        'address_line_one' => '117 Spencer St.',
        'address_line_two' => 'My Childhood House',
    ]);

    expect($model->address->lineOne)->toBe('117 Spencer St.');
});

test('with castable interface', function () {
    $model = new TestEloquentModelWithCustomCast();

    $model->setRawAttributes([
        'value_object_with_caster' => serialize(new ValueObject('hello')),
    ]);

    expect($model->value_object_with_caster)->toBeInstanceOf(ValueObject::class);
    expect($model->toArray()['value_object_with_caster'])->toBe(serialize(new ValueObject('hello')));

    $model->setRawAttributes([
        'value_object_caster_with_argument' => null,
    ]);

    expect($model->value_object_caster_with_argument)->toBe('argument');

    $model->setRawAttributes([
        'value_object_caster_with_caster_instance' => serialize(new ValueObject('hello')),
    ]);

    expect($model->value_object_caster_with_caster_instance)->toBeInstanceOf(ValueObject::class);
});

test('get from undefined cast', function () {
    $this->expectException(InvalidCastException::class);

    $model = new TestEloquentModelWithCustomCast();
    $model->undefined_cast_column;
});

test('set to undefined cast', function () {
    $this->expectException(InvalidCastException::class);

    $model = new TestEloquentModelWithCustomCast();
    expect($model->hasCast('undefined_cast_column'))->toBeTrue();

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

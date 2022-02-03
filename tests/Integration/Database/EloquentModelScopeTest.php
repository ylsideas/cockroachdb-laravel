<?php

use Illuminate\Database\Eloquent\Model;

uses(DatabaseTestCase::class);

test('model has scope', function () {
    $model = new TestScopeModel1();

    $this->assertTrue($model->hasNamedScope('exists'));
});

test('model does not have scope', function () {
    $model = new TestScopeModel1();

    $this->assertFalse($model->hasNamedScope('doesNotExist'));
});

// Helpers
function scopeExists()
{
    return true;
}

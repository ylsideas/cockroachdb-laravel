<?php


uses(DatabaseTestCase::class);

test('model has scope', function () {
    $model = new TestScopeModel1();

    expect($model->hasNamedScope('exists'))->toBeTrue();
});

test('model does not have scope', function () {
    $model = new TestScopeModel1();

    expect($model->hasNamedScope('doesNotExist'))->toBeFalse();
});

// Helpers
function scopeExists()
{
    return true;
}

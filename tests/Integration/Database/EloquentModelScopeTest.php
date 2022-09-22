<?php

namespace YlsIdeas\CockroachDb\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;

class EloquentModelScopeTest extends DatabaseTestCase
{
    public function test_model_has_scope()
    {
        $model = new TestScopeModel1();

        $this->assertTrue($model->hasNamedScope('exists'));
    }

    public function test_model_does_not_have_scope()
    {
        $model = new TestScopeModel1();

        $this->assertFalse($model->hasNamedScope('doesNotExist'));
    }
}

class TestScopeModel1 extends Model
{
    public function scopeExists()
    {
        return true;
    }
}

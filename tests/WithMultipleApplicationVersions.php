<?php

namespace YlsIdeas\CockroachDb\Tests;

use Illuminate\Support\Facades\App;

trait WithMultipleApplicationVersions
{
    public function skipIfClassMissing(string $class): void
    {
        if (! class_exists($class)) {
            $this->markTestSkipped(sprintf('Class %s does not exist for the test to continue', $class));
        }
    }

    public function skipIfOlderThan(string $version): void
    {
        if (version_compare(App::version(), $version, '<')) {
            $this->markTestSkipped('Not included before '. $version);
        }
    }

    public function skipIfNewerThan(string $version): void
    {
        if (version_compare(App::version(), $version, '>=')) {
            $this->markTestSkipped('Legacy test pre '. $version);
        }
    }

    /**
     * @param string $version
     * @param mixed|callable $onTrue
     * @param mixed|callable $onFalse
     * @param string $operator
     * @return mixed
     */
    public function executeOnVersion(string $version, mixed $onTrue, mixed $onFalse, string $operator = '>=')
    {
        return version_compare(App::version(), $version, $operator) ? value($onTrue) : value($onFalse);
    }
}

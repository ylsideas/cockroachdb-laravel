<?php

namespace YlsIdeas\CockroachDb;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use YlsIdeas\CockroachDb\Commands\CockroachDbCommand;

class CockroachDbServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('cockroachdb-laravel')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_cockroachdb-laravel_table')
            ->hasCommand(CockroachDbCommand::class);
    }
}
